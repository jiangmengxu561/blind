<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Email;
use app\common\library\Ems;
use app\common\library\Sms;
use fast\Random;
use think\Config;
use think\Db;
use think\Session;
use think\Validate;

/**
 * 会员接口
 */
class User extends Api
{
    protected $noNeedLogin = ['login', 'mobilelogin', 'register', 'resetpwd', 'changeemail', 'changemobile', 'third','emailtest','emailregister','reset','emailReset'];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();

        if (!Config::get('fastadmin.usercenter')) {
            $this->error(__('User center already closed'));
        }

    }

    /**
     * 会员中心
     */
    public function index()
    {
        $this->success('', ['welcome' => $this->auth->nickname]);
    }

    /**
     * 会员登录
     *
     * @ApiMethod (POST)
     * @param string $account  账号
     * @param string $password 密码
     */
    public function login()
    {
        $account = $this->request->post('account');
        $password = $this->request->post('password');
        if (!$account || !$password) {
            $this->error(__('Invalid parameters'));
        }
        $ret = $this->auth->login($account, $password);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 手机验证码登录
     *
     * @ApiMethod (POST)
     * @param string $mobile  手机号
     * @param string $captcha 验证码
     */
    public function mobilelogin()
    {
        $mobile = $this->request->post('mobile');
        $captcha = $this->request->post('captcha');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        if (!Sms::check($mobile, $captcha, 'mobilelogin')) {
            $this->error(__('Captcha is incorrect'));
        }
        $user = \app\common\model\User::getByMobile($mobile);
        if ($user) {
            if ($user->status != 'normal') {
                $this->error(__('Account is locked'));
            }
            //如果已经有账号则直接登录
            $ret = $this->auth->direct($user->id);
        } else {
            $ret = $this->auth->register($mobile, Random::alnum(), '', $mobile, []);
        }
        if ($ret) {
            Sms::flush($mobile, 'mobilelogin');
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 手机号注册会员
     *
     * @ApiMethod (POST)
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $email    邮箱
     * @param string $mobile   手机号
     * @param string $code     验证码
     */
    public function register()
    {
//        $username = $this->request->post('username');
        $mobile = $this->request->post('mobile');
        $type = $this->request->param('type');

        $password = $this->request->post('password');
//        $email = $this->request->post('email');
        $code = $this->request->post('code');
        if ( !$password) {
            $this->error(__('Invalid parameters'));
        }
        if ($mobile && !Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        $ret = Sms::check($mobile, $code, $type);
        if (!$ret) {
            $this->error(__('Captcha is incorrect'));
        }
        $randStr = str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890');
        $rand = substr($randStr,0,6);

        $ret = $this->auth->register($mobile, $password, '', $mobile, ['extension'=>$rand]);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Sign up successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }


    /**
     * 手机号重置密码
     *
     * @ApiMethod (POST)
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $email    邮箱
     * @param string $mobile   手机号
     * @param string $code     验证码
     */
    public function reset()
    {
        $mobile = $this->request->post('mobile');
        $type = $this->request->param('type');
        $password = $this->request->post('password');
        $code = $this->request->post('code');
        if ( !$password) {
            $this->error(__('Invalid parameters'));
        }
        if ($mobile && !Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        $ret = Sms::check($mobile, $code, $type);
        if (!$ret) {
            $this->error(__('Captcha is incorrect'));
        }
        $salt=Random::alnum();

        $password = $this->auth->getEncryptPassword($password, $salt);
        $res = Db::name('user')->where('mobile',$mobile)->update(['password'=>$password,'salt'=>$salt]);
        if ($res){
            $this->success('重置成功,请重新登录');
        }else{
            $this->error('系统异常，请稍后重试');
        }
    }

    /**
     * 发送测试邮件
     * @internal
     */
    public function emailtest()
    {

        $receiver = $this->request->post("receiver");
        $type = $this->request->param('type');
        if (!Validate::is($receiver, "email")) {
            $this->error(__('请输入正确的电子邮件'));
        }
        $res = substr($receiver, strripos($receiver, "@") + 1);
        if ($res!='163.com' && $res!='qq.com'){
            $this->error(__('请输入正确的邮箱格式'));
        }
        if ($res = 'qq.com'){
            $row=[
                'mail_type'=>1,
                'mail_smtp_host'=>'smtp.qq.com',
                'mail_smtp_port'=>465,
                'mail_smtp_user'=>'盲盒',
                'mail_smtp_pass'=>'abqlrfiylwxabebf',
                'mail_verify_type'=>2,
                'mail_from'=>'2081358646@qq.com',
            ];
            $randStr = str_shuffle('1234567890');
            $code = substr($randStr,0,6);

            if ($receiver) {
//                print_r(\think\Config::get('site'));die;
                \think\Config::set('site', array_merge(\think\Config::get('site'), $row));
                $email = new Email;

                $start=time();//当前时间
                $end=time()-5*60;
                $data['createtime'] = array('BETWEEN',array(date("Y-m-d H:i:s",$end),date("Y-m-d H:i:s",$start)));

                $arr = Db::name('ems')
                    ->where('event',$type)
                    ->where('email',$receiver)
                    ->where('createtime','<',$start)
                    ->where('createtime','>',$end)
                    ->value('code');
//                print_r($arr);die;
                if ($arr){
                    $this->error('验证码发送频繁');
                }else{

                $result = $email
                    ->to($receiver)
                    ->subject(__("来自盲盒的邮件", config('site.name')))
                    ->message('<div style="min-height:550px; padding: 100px 55px 200px;">' . __('您的验证码为【'.$code.'】,五分钟内有效', config('site.name')) . '</div>')
                    ->send();

                if ($result) {
                    $data=[
                        'event'=>$type,
                        'email'=>$receiver,
                        'code'=>$code,
                        'createtime'=>time()
                    ];
                        Db::name('ems')->insert($data);
                    $this->success('验证码发送成功');
                    } else {
                    $this->error($email->getError());
                }
                }
            } else {
                $this->error(__('Invalid parameters'));
            }
        }else{
            $row=[
                'mail_type'=>1,
                'mail_smtp_host'=>'smtp.163.com',
                'mail_smtp_port'=>465,
                'mail_smtp_user'=>'盲盒',
                'mail_smtp_pass'=>'SBRIBRLEQULAIVFA',
                'mail_verify_type'=>2,
                'mail_from'=>'2081358646@qq.com',
            ];
            $randStr = str_shuffle('1234567890');
            $code = substr($randStr,0,6);

            if ($receiver) {
//                print_r(\think\Config::get('site'));die;
                \think\Config::set('site', array_merge(\think\Config::get('site'), $row));
                $email = new Email;

                $result = $email
                    ->to($receiver)
                    ->subject(__("来自盲盒的邮件", config('site.name')))
                    ->message('<div style="min-height:550px; padding: 100px 55px 200px;">' . __('您的验证码为【'.$code.'】', config('site.name')) . '</div>')
                    ->send();

                if ($result) {
                    Session::set($receiver,$code);
                    $this->success();
                } else {
                    $this->error($email->getError());
                }
            } else {
                $this->error(__('Invalid parameters'));
            }
        }
//        print_r($row);
//        echo "-------";
//        print_r($receiver);die;

    }

    /**
     * 邮箱注册会员
     *
     * @ApiMethod (POST)
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $email    邮箱
     * @param string $mobile   手机号
     * @param string $code     验证码
     */
    public function emailregister()
    {
        $receiver = $this->request->post('receiver');
        $password = $this->request->post('password');
        $type = $this->request->param('type');

        $start=time();//当前时间
        $end=time()-5*60;

        $code1    = Db::name('ems')
                    ->where('event',$type)
                    ->where('email',$receiver)
                    ->where('createtime','<',$start)
                    ->where('createtime','>',$end)
                    ->value('code');
//        print_r($code1);die;
        $code = $this->request->post('code');
        if ($code1!=$code){
            $this->error(__('验证码不正确'));
        }
        if ( !$password) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::is($receiver, "email")) {
            $this->error(__('请输入正确的电子邮件'));
        }
        $randStr = str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890');
        $rand = substr($randStr,0,6);
        $ret = $this->auth->register($receiver, $password, $receiver, '', ['extension'=>$rand]);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];

            $data1 = [
                'userid' =>$data['userinfo']['id'],
                'm_id' => Config::get('site.songmang')
            ];
            Db::name('mang')->insert($data1);
            $this->success(__('Sign up successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }


    /**
     * 邮箱重置密码
     *
     * @ApiMethod (POST)
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $email    邮箱
     * @param string $mobile   手机号
     * @param string $code     验证码
     */
    public function emailReset()
    {
        $receiver = $this->request->post('receiver');
        $password = $this->request->post('password');
        $type = $this->request->param('type');

        $start=time();//当前时间
        $end=time()-5*60;

        $code1    = Db::name('ems')
            ->where('event',$type)
            ->where('email',$receiver)
            ->where('createtime','<',$start)
            ->where('createtime','>',$end)
            ->value('code');
        $code = $this->request->post('code');
        if ($code1!=$code){
            $this->error(__('验证码不正确'));
        }
        if ( !$password) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::is($receiver, "email")) {
            $this->error(__('请输入正确的电子邮件'));
        }
            $salt=Random::alnum();

        $password = $this->auth->getEncryptPassword($password, $salt);
        $res = Db::name('user')->where('email',$receiver)->update(['password'=>$password,'salt'=>$salt]);
        if ($res){
            $this->success('重置成功,请重新登录');
        }else{
            $this->error('系统异常，请稍后重试');
        }
    }


    /**
     * 退出登录
     * @ApiMethod (POST)
     */
    public function logout()
    {
        if (!$this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }
        $this->auth->logout();
        $this->success(__('Logout successful'));
    }

    /**
     * 修改会员个人信息
     *
     * @ApiMethod (POST)
     * @param string $avatar   头像地址
     * @param string $username 用户名
     * @param string $nickname 昵称
     * @param string $bio      个人简介
     */
    public function profile()
    {
        $user = $this->auth->getUser();
        $username = $this->request->post('username');
        $nickname = $this->request->post('nickname');
        $bio = $this->request->post('bio');
        $avatar = $this->request->post('avatar', '', 'trim,strip_tags,htmlspecialchars');
        if ($username) {
            $exists = \app\common\model\User::where('username', $username)->where('id', '<>', $this->auth->id)->find();
            if ($exists) {
                $this->error(__('Username already exists'));
            }
            $user->username = $username;
        }
        if ($nickname) {
            $exists = \app\common\model\User::where('nickname', $nickname)->where('id', '<>', $this->auth->id)->find();
            if ($exists) {
                $this->error(__('Nickname already exists'));
            }
            $user->nickname = $nickname;
        }
        $user->bio = $bio;
        $user->avatar = $avatar;
        $user->save();
        $this->success();
    }

    /**
     * 修改邮箱
     *
     * @ApiMethod (POST)
     * @param string $email   邮箱
     * @param string $captcha 验证码
     */
    public function changeemail()
    {
//        $user = $this->auth->getUser();
        $email = $this->request->post('email');
        $captcha = $this->request->post('captcha');
        if (!$email || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::is($email, "email")) {
            $this->error(__('Email is incorrect'));
        }
//        if (\app\common\model\User::where('email', $email)->where('id', '<>', $user->id)->find()) {
//            $this->error(__('Email already exists'));
//        }
        $result = Ems::check($email, $captcha, 'changeemail');
        if (!$result) {
            $this->error(__('Captcha is incorrect'));
        }
//        $verification = $user->verification;
//        $verification->email = 1;
//        $user->verification = $verification;
//        $user->email = $email;
//        $user->save();

        Ems::flush($email, 'changeemail');
        $this->success();
    }

    /**
     * 修改手机号
     *
     * @ApiMethod (POST)
     * @param string $mobile  手机号
     * @param string $captcha 验证码
     */
    public function changemobile()
    {
        $user = $this->auth->getUser();
        $mobile = $this->request->post('mobile');
        $captcha = $this->request->post('captcha');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        if (\app\common\model\User::where('mobile', $mobile)->where('id', '<>', $user->id)->find()) {
            $this->error(__('Mobile already exists'));
        }
        $result = Sms::check($mobile, $captcha, 'changemobile');
        if (!$result) {
            $this->error(__('Captcha is incorrect'));
        }
        $verification = $user->verification;
        $verification->mobile = 1;
        $user->verification = $verification;
        $user->mobile = $mobile;
        $user->save();

        Sms::flush($mobile, 'changemobile');
        $this->success();
    }

    /**
     * 第三方登录
     *
     * @ApiMethod (POST)
     * @param string $platform 平台名称
     * @param string $code     Code码
     */
    public function third()
    {
        $url = url('user/index');
        $platform = $this->request->post("platform");
        $code = $this->request->post("code");
        $config = get_addon_config('third');
        if (!$config || !isset($config[$platform])) {
            $this->error(__('Invalid parameters'));
        }
        $app = new \addons\third\library\Application($config);
        //通过code换access_token和绑定会员
        $result = $app->{$platform}->getUserInfo(['code' => $code]);
        if ($result) {
            $loginret = \addons\third\library\Service::connect($platform, $result);
            if ($loginret) {
                $data = [
                    'userinfo'  => $this->auth->getUserinfo(),
                    'thirdinfo' => $result
                ];
                $this->success(__('Logged in successful'), $data);
            }
        }
        $this->error(__('Operation failed'), $url);
    }

    /**
     * 重置密码
     *
     * @ApiMethod (POST)
     * @param string $mobile      手机号
     * @param string $newpassword 新密码
     * @param string $captcha     验证码
     */
    public function resetpwd()
    {
        $type = $this->request->post("type");
        $mobile = $this->request->post("mobile");
        $email = $this->request->post("email");
        $newpassword = $this->request->post("newpassword");
        $captcha = $this->request->post("captcha");
        if (!$newpassword || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        //验证Token
        if (!Validate::make()->check(['newpassword' => $newpassword], ['newpassword' => 'require|regex:\S{6,30}'])) {
            $this->error(__('Password must be 6 to 30 characters'));
        }
        if ($type == 'mobile') {
            if (!Validate::regex($mobile, "^1\d{10}$")) {
                $this->error(__('Mobile is incorrect'));
            }
            $user = \app\common\model\User::getByMobile($mobile);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Sms::check($mobile, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Sms::flush($mobile, 'resetpwd');
        } else {
            if (!Validate::is($email, "email")) {
                $this->error(__('Email is incorrect'));
            }
            $user = \app\common\model\User::getByEmail($email);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Ems::check($email, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Ems::flush($email, 'resetpwd');
        }
        //模拟一次登录
        $this->auth->direct($user->id);
        $ret = $this->auth->changepwd($newpassword, '', true);
        if ($ret) {
            $this->success(__('Reset password successful'));
        } else {
            $this->error($this->auth->getError());
        }
    }
}
