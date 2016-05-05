<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Uzer */

$this->title = 'Create Uzer';
$this->params['breadcrumbs'][] = ['label' => 'Uzers', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="uzer-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
