<?php


namespace doublesecretagency\viewcount\console\controllers;

use yii\console\Controller;
use doublesecretagency\viewcount\services\AnalyticsService;
use doublesecretagency\viewcount\ViewCount;

class AnalyticsController extends Controller
{

    public function actionBuildNodeAnalyticsTable()
    {

        echo ViewCount::$plugin->analytics->buildNodeAnalyticsTable();
    }

}