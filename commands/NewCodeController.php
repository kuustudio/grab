<?php
/**
 * Created by PhpStorm.
 * User: wang.haibo
 * Date: 2018/11/22
 * Time: 17:30
 */

namespace app\commands;


use app\components\AdditionSubtraction;
use app\components\NewGrab;
use yii\console\Controller;

class NewCodeController extends Controller
{

    /**
     * 江西
     */
    public function actionJx() {
        new NewGrab(1);
    }

    /**
     * 广东
     */
    public function actionGd() {
        new NewGrab(2);
    }

    /**
     * 山东
     */
    public function actionSd() {
        new NewGrab(3);
    }

    /**
     * 上海
     */
    public function actionSh() {
        new NewGrab(4);
    }

    /**
     * 江西玩法 加减报警
     */
    public function actionPlayJx() {
        new AdditionSubtraction(1);
    }

    /**
     * 广东玩法 加减报警
     */
    public function actionPlayGd() {
        new AdditionSubtraction(2);
    }

    /**
     * 山东玩法 加减报警
     */
    public function actionPlaySd() {
        new AdditionSubtraction(3);
    }

}