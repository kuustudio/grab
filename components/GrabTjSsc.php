<?php
/**
 * Created by PhpStorm.
 * User: yeyun
 * Date: 16-9-5
 * Time: 下午5:10
 */

namespace app\components;

use app\models\AnalysisTjssc;
use app\models\Log;
use app\models\Comparison;
use app\models\Configure;
use app\models\Tjssc;
use app\models\Mailbox;
use Yii;

//设置时区
date_default_timezone_set('PRC');

class GrabTjSsc
{

    /**
     * 信息来源网：http://tools.cjcp.com.cn/gl/ssc/tj.html
     * POST 数据 打开浏览器调试模式 查看 AJAX 加载地址：http://tools.cjcp.com.cn/gl/ssc/filter/kjdata.php
     * 最新开奖信息查询网
     */
    const URL = 'http://tools.cjcp.com.cn/gl/ssc/filter/kjdata.php';

    /* 抓取后的数据 array */
    private $data;

    /* 天津时时彩 上传的数据包1 数组 */
    private $data_packet;

    /* 天津时时彩 上传的数据包2 数组 */
    private $data_packet_2;

    /* 天津时时彩 上传的数据包1 txt 文本内容 */
    private $data_packet_txt;

    /* 天津时时彩 上传的数据包2 txt 文本内容 */
    private $data_packet_txt_2;

    public function __construct()
    {
        ini_set('memory_limit','888M');
        $this->get_data();     //抓取数据
        $this->insert_mysql(); //记录数据
        $this->reserve_warning(); //预定号码报警
        $this->warning();      //邮件报警
    }

    /**
     * 预定号码报警
     */
    private function reserve_warning(){
        new Reserve('tj');
    }

    /**
     * 邮件报警
     */
    private function warning(){
        new Alarm('tj');
    }

    /**
     * curl 访问 开奖数据
     */
    private function get_data(){
        $post_data = ['lotteryType'=>'tianjinssc']; //天津时时彩
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT,60);   //只需要设置一个秒的数量就可以  60超时
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $output = curl_exec($ch);
        curl_close($ch);

        $tjCodeArr = json_decode($output,true);
        if(!is_array($tjCodeArr)){
            $this->setLog(false,'天津时时彩-数据抓取失败');
            exit('天津时时彩-数据抓取失败,请尽快联系网站管理员'."\r\n");
        }

        //期号
        if(!isset($tjCodeArr['kaijiang']['qihao'])){
            $this->setLog(false,'天津时时彩-开奖期号抓取失败');
            exit('天津时时彩-开奖期号抓取失败,请尽快联系网站管理员'."\r\n");
        }

        //开奖时间
        if(!isset($tjCodeArr['kaijiang']['riqi'])){
            $this->setLog(false,'天津时时彩-开奖时间抓取失败');
            exit('天津时时彩-开奖时间抓取失败,请尽快联系网站管理员'."\r\n");
        }

        //开奖号码
        if(!isset($tjCodeArr['kaijiang']['jianghao'])){
            $this->setLog(false,'天津时时彩-开奖号码抓取失败');
            exit('天津时时彩-开奖号码抓取失败,请尽快联系网站管理员'."\r\n");
        }

        //期号
        $qihao = $tjCodeArr['kaijiang']['qihao'];
        //开奖时间
        $kjsj = $tjCodeArr['kaijiang']['riqi'];
        //开奖号码
        $code = $tjCodeArr['kaijiang']['jianghao'];
        $this->data = ['qihao'=>$qihao, 'kjsj'=>$kjsj, 'code'=>$code];
    }

    /**
     * 记录到 mysql
     */
    private function insert_mysql(){
        $exists = Tjssc::findOne(['qishu'=>$this->data['qihao'],'code'=>$this->data['code']]);
        if($exists){
            exit("天津时时彩数据已经采集过了 时间:".date('Y-m-d H:i:s')."\r\n");
        }

        //开奖前三 号码
        $q3 = $this->data['code'][0].$this->data['code'][1].$this->data['code'][2];
        //开奖中三 号码
        $z3 = $this->data['code'][1].$this->data['code'][2].$this->data['code'][3];
        //后奖中三 号码
        $h3 = $this->data['code'][2].$this->data['code'][3].$this->data['code'][4];
        $this->analysisCode();

        list($q3_data1_lucky,$q3_data1_regert,$q3_data2_lucky,$q3_data2_regert) = $this->isLucky($q3); //前三中奖情况
        list($z3_data1_lucky,$z3_data1_regert,$z3_data2_lucky,$z3_data2_regert) = $this->isLucky($z3); //中三是否中奖
        list($h3_data1_lucky,$h3_data1_regert,$h3_data2_lucky,$h3_data2_regert) = $this->isLucky($h3); //侯三是否中奖

        //前三是组6还是组3
        $q3_type = $this->is_type($q3);
        //中三是组6还是组3
        $z3_type = $this->is_type($z3);
        //后三是组6还是组3
        $h3_type = $this->is_type($h3);

        //开启事物
        $innerTransaction = Yii::$app->db->beginTransaction();
        try{
            /* 插入 开奖记录表数据 */
            $tjsscModel = new Tjssc();
            $tjsscModel->qishu             = $this->data['qihao'];
            $tjsscModel->one               = $this->data['code'][0];
            $tjsscModel->two               = $this->data['code'][1];
            $tjsscModel->three             = $this->data['code'][2];
            $tjsscModel->four              = $this->data['code'][3];
            $tjsscModel->five              = $this->data['code'][4];
            $tjsscModel->code              = $this->data['code'];
            $tjsscModel->front_three_type  = $q3_type;
            $tjsscModel->center_three_type = $z3_type;
            $tjsscModel->after_three_type  = $h3_type;
            $tjsscModel->kj_time           = $this->data['kjsj'];
            $tjsscModel->time              = time();
            $tjsscModel->save();

            /* 插入 开奖记录关联的 数据分析表 数据包1解析的结果 */
            $analysisTjsscModel = new AnalysisTjssc();
            $analysisTjsscModel->tjssc_id                = $tjsscModel->id;
            $analysisTjsscModel->front_three_lucky_txt   = $q3_data1_lucky;
            $analysisTjsscModel->front_three_regret_txt  = $q3_data1_regert;
            $analysisTjsscModel->center_three_lucky_txt  = $z3_data1_lucky;
            $analysisTjsscModel->center_three_regret_txt = $z3_data1_regert;
            $analysisTjsscModel->after_three_lucky_txt   = $h3_data1_lucky;
            $analysisTjsscModel->after_three_regret_txt  = $h3_data1_regert;
            $analysisTjsscModel->data_txt                = $this->data_packet_txt;
            $analysisTjsscModel->type                    = 1;
            $analysisTjsscModel->time                    = time();
            $analysisTjsscModel->save();

            /* 插入 开奖记录关联的 数据分析表 数据包2解析的结果 */
            $analysisTjsscModel = new AnalysisTjssc();
            $analysisTjsscModel->tjssc_id                = $tjsscModel->id;
            $analysisTjsscModel->front_three_lucky_txt   = $q3_data2_lucky;
            $analysisTjsscModel->front_three_regret_txt  = $q3_data2_regert;
            $analysisTjsscModel->center_three_lucky_txt  = $z3_data2_lucky;
            $analysisTjsscModel->center_three_regret_txt = $z3_data2_regert;
            $analysisTjsscModel->after_three_lucky_txt   = $h3_data2_lucky;
            $analysisTjsscModel->after_three_regret_txt  = $h3_data2_regert;
            $analysisTjsscModel->data_txt                = $this->data_packet_txt_2;
            $analysisTjsscModel->type                    = 2;
            $analysisTjsscModel->time                    = time();
            $analysisTjsscModel->save();

            $innerTransaction->commit(); //事物提交

            $this->setLog(true,'天津时时彩数据抓取成功');
            echo "天津时时彩数据抓取成功 时间:".date('Y-m-d H:i:s')."\r\n";
        } catch (\Exception $e){
            $innerTransaction->rollBack();
            $this->setLog(false,'天津时时彩数据与数据分析存入失败');
            exit("天津时时彩数据分析存入失败 时间:".date('Y-m-d H:i:s')."\r\n");
        }
    }

    /**
     * 是组6 还是组3
     */
    private function is_type($code){
        $codeArr = str_split($code);
        //是组6
        if(count($codeArr) == count(array_unique($codeArr))){
            return 1;
        }else{
            //是组三
            return 2;
        }
    }

    /**
     * 解析 上传数据
     */
    private function analysisCode(){
        //天津时时彩的数据包1
        $model = Comparison::findOne(['type'=>3]);
        $data = $model->txt;
        $this->data_packet_txt = $model->txt;
        $dataTxts = str_replace("\r\n", ' ', $data); //将回车转换为空格
        $dataArr = explode(' ',$dataTxts);
        $dataArr = array_filter($dataArr);
        $this->data_packet = $dataArr;

        //天津时时彩的数据包2
        $model = Comparison::findOne(['type'=>33]);
        $data = $model->txt;
        $this->data_packet_txt_2 = $model->txt;
        $dataTxts = str_replace("\r\n", ' ', $data); //将回车转换为空格
        $dataArr = explode(' ',$dataTxts);
        $dataArr = array_filter($dataArr);
        $this->data_packet_2 = $dataArr;
    }

    /**
     * 数据包里的号码是否中奖
     * @param $code 需要查询的 前三 or 中三 or 后三号码;
     * @return bool
     */
    private function isLucky($code){
        //数据包1 中的中奖号码与未中奖号码
        $data_packet = $this->data_packet;
        $lucky = null;  //中奖号码
        $regert = null; //未中奖号码
        foreach ($data_packet as $key=>$val){
            if($val == $code){
                $lucky = $val;
            }else{
                $regert .= $val."\r\n";
            }
        }

        $data1_lucky = $lucky;
        $data1_regert = $regert;

        //数据包2 中的中奖号码与未中奖号码
        $data_packet = $this->data_packet_2;
        $lucky = null;  //中奖号码
        $regert = null; //未中奖号码
        foreach ($data_packet as $key=>$val){
            if($val == $code){
                $lucky = $val;
            }else{
                $regert .= $val."\r\n";
            }
        }

        $data2_lucky = $lucky;   //数据包2中的中奖号码
        $data2_regert = $regert; //数据包2中的未中奖号码

        return [$data1_lucky,$data1_regert,$data2_lucky,$data2_regert];

    }


    /**
     * 记录日志
     * @param bool $state      操作状态;
     * @param string $content  操作内容;
     */
    private function setLog($state = true, $content = ''){
        $state == true ? $type=1 : $type = 2;
        //抓取网页失败 记录日志
        $logModel = new Log();
        $logModel->type = $type;
        $logModel->content = $content;
        $logModel->time = time();
        $logModel->save();
    }

}