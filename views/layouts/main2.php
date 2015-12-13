<?php
use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use app\assets\AppAsset;

/* @var $this \yii\web\View */
/* @var $content string */

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta property="qc:admins" content="6003457707611151566375" />
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <link rel="stylesheet" type="text/css" href="/components/cropper/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="/components/bootstrap-3.3.5-dist/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/components/bootstrap-3.3.5-dist/css/bootstrap-theme.min.css">
    <link rel="stylesheet" type="text/css" href="/components/toastr/toastr.min.css">
    <link rel="stylesheet" type="text/css" href="/components/artDialog/css/ui-dialog.css">
    <link rel="stylesheet" type="text/css" href="/css/main.css">

    <script src="/js/jquery.min.js"></script>
    <script src="/components/bootstrap-3.3.5-dist/js/bootstrap.min.js"></script>
    <script src="/components/toastr/toastr.min.js"></script>
    <script src="/components/artDialog/dist/dialog-min.js"></script>
    <script src="/components/headroom/headroom.min.js"></script>
    <script src="/components/headroom/jquery.headroom.js"></script>
    <?php $this->head() ?>
</head>
<body>

<?php $this->beginBody() ?>

<header class="header header--fixed hide-from-print animated slideDown">
    <?php
    NavBar::begin([
        'brandLabel' => '小蛮牛',
        'brandUrl' => Yii::$app->homeUrl,
        'options' => ['id'=>'nav'],
    ]);
    echo Nav::widget([
        'encodeLabels' => false,
        'options' => ['class' => 'navbar-nav navbar-right'],
        'items' => [
            ['label' => '江西页面', 'url' => ['/home?type=1']],
            ['label' => '广东页面', 'url' => ['/home?type=2']],
            ['label' => '山东页面', 'url' => ['/home?type=3']],
            Yii::$app->user->isGuest ?
                ['label' => '登陆后台', 'linkOptions'=>['class'=>'settled'], 'url' => ['/admin/login']] :
                ['label' => '退出登陆 (' . Yii::$app->user->identity->username . ')',
                    'url' => ['/site/logout'],
                    'linkOptions' => ['data-method' => 'post']
                ],
            Yii::$app->user->isGuest ?
                ['label' => '登陆后台', 'linkOptions'=>['class'=>'hidden'], 'url' => ['/admin/login']] :
                ['label' => '进入后台', 'linkOptions'=>['class'=>'settled'], 'url' => ['/admin/manage']],
        ],
    ]);
    NavBar::end();
    ?>

</header>

<div id="main-content">
    <?= Breadcrumbs::widget([
        'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
    ]) ?>
    <?= $content ?>
</div>

<!--<footer class="footer">-->
<!--    <div class="container">-->
<!--        <p class="pull-left">&copy; My Company --><?//= date('Y') ?><!--</p>-->
<!--    </div>-->
<!--</footer>-->
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
