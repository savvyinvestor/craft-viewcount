<?php
/**
 * View Count plugin for Craft CMS
 *
 * Count the number of times an element has been viewed.
 *
 * @author    Double Secret Agency
 * @link      https://www.doublesecretagency.com/
 * @copyright Copyright (c) 2019 Double Secret Agency
 */

namespace doublesecretagency\viewcount\services;

use craft\helpers\Json;
use yii\db\Expression;

use Craft;
use craft\base\Component;
use craft\elements\db\ElementQuery;

use doublesecretagency\viewcount\ViewCount;
use doublesecretagency\viewcount\records\ElementTotal;
use doublesecretagency\viewcount\records\UserHistory;


/**
 * Class Query
 * @since 1.0.0
 */
class Query extends Component
{

    public function filterResults(array $filters)
    {

       $memberName = $this->splitNameFromFilters($filters['member_name']);
       $title = $filters['entry_title'];
       $companyAuthor = $filters['company_author'];
       $entryCompany = $filters['entry_company'];
       $entryType = $filters['entry_type'];
       $clickedOn = $filters['clicked_on'];
       $topics = $filters['topics'];
       $author = $this->splitNameFromFilters($filters['author']);

       if(in_array('date_from', $filters)){
            $dateFrom = $filters['date_from'];
            $dateTo = $filters['date_to'];
       }

       // Additional
       $consentNeeded = $filters['consent_needed'];
     

        $rows = (new craft\db\Query())
                ->select([
                'members.firstName', 
                'members.lastName', 
                'members.email',
                'c.title',
                'c.field_paperType',
                'c.dateCreated',
                'c.field_country',
                'c.field_city',
                'cont_auth.field_userCompanyName',
                'c.field_jobTitle',
                'c.field_consentNeeded', 
                'users_auth.firstName', 
                'users_auth.lastName',
                'topics.title'
                ])
                ->from('viewcount_viewlog')
                ->leftJoin('{{%content}} c', '[[viewcount_viewlog.elementId]] = [[c.elementId]]')
                ->innerJoin('{{%entries}}', '[[entries.id]] = [[c.elementId]]')
                ->leftJoin('{{%users}} members', '[[viewcount_viewlog.userId]] = [[members.id]]')
                ->leftJoin('{{%content}} cont_auth', '[[c.elementId]] = [[entries.authorId]]')
                ->leftJoin('{{%users}} users_auth', '[[entries.authorId]] = [[members.id]]')
                ->innerJoin('{{%relations}}', '[[entries.id]] = [[relations.sourceId]]')
                ->innerJoin('{{%categories}}', '[[categories.id]] = [[relations.targetId]]')
                ->leftJoin('{{%content}} topics', '[[categories.id]] = [[topics.elementId]]')

                // ->where(['like', 'first_name', $memberName['first_name'], false])
                // ->andWhere(['like', 'last_name', $memberName['last_name'], false])
                // ->andWhere(['like', 'title', $title, false])
                // ->andWhere(['like', 'companys_author_name', $companyAuthor, false])
                // ->andWhere(['like', 'company_name', $entryCompany, false])
                // ->andWhere(['type' => $entryType])
                // ->andWhere(['clicked_on' => $clickedOn])
                // ->andWhere(['topics' => $topics])
                // ->andWhere(['like', 'author_first_name', $author['first_name'], false])
                // ->andWhere(['like', 'author_last_name', $author['last_name'], false])
                // ->andWhere(['consent_needed' => $consentNeeded])
                // ->andWhere(['created' => $dateFrom])
                // ->andWhere(['created' <= $dateTo])
                // ->all();

                ->where(['c.id' => 725089])
                ->all();

        return $rows;
    }

    private function splitNameFromFilters(string $filter):array
    {
        $memberName = array(); 

        if($filter != ''){
            $member = explode(' ', $filter);
        }else{
            $memberName['first_name'] = '%%';
            $memberName['last_name'] = '%%'; 
            return $memberName;
        }

        if(sizeof($member) > 1){
            $memberName['first_name']  = '%' . $member[0] . '%';
            $memberName['last_name'] = '%' . $member[1] . '%';
        }else{
            $memberName['first_name']  = '%' . $member[0] . '%';
            $memberName['last_name'] = '%%'; 
        } 

        return $memberName;

    }

    //
    public function total($elementId, $key = null): int
    {
        $record = ElementTotal::findOne([
            'elementId' => $elementId,
            'viewKey'   => $key,
        ]);
        return ($record ? $record->viewTotal : 0);
    }

    // ========================================================================

    //
    public function userHistory($userId = null): array
    {
        if (!$userId) {
            return [];
        }
        $record = UserHistory::findOne([
            'id' => $userId,
        ]);
        if (!$record) {
            return [];
        }
        return Json::decode($record->history);
    }

    //
    public function orderByViews(ElementQuery $query, $key = null)
    {
        // Collect and sort elementIds
        $elementIds = $this->_elementIdsByViews($key);

        // If no element IDs, bail
        if (!$elementIds) {
            return false;
        }

        // Match order to elementIds
        $ids = implode(', ', $elementIds);
        $query->orderBy = [new Expression("field([[elements.id]], {$ids}) desc")];
    }

    //
    private function _elementIdsByViews($key)
    {
        // If key isn't valid, bail
        if (!ViewCount::$plugin->viewCount->validKey($key)) {
            return false;
        }

        // Adjust conditions based on whether a key was provided
        if (null === $key) {
            $conditions = '[[totals.viewKey]] is null';
        } else {
            $conditions = ['[[totals.viewKey]]' => $key];
        }

        // Construct order SQL
        $total = 'ifnull([[totals.viewTotal]], 0)';
        $order = "{$total} desc, [[elements.id]] desc";

        // Join with elements table to sort by total
        $elementIds = (new craft\db\Query())
            ->select('[[elements.id]]')
            ->from('{{%elements}} elements')
            ->where($conditions)
            ->leftJoin('{{%viewcount_elementtotals}} totals', '[[elements.id]] = [[totals.elementId]]')
            ->orderBy([new Expression($order)])
            ->column();

        // Return elementIds
        return array_reverse($elementIds);
    }


    // == DEPRECATED ==
    // public function filterResults(array $filters)
    // {

    //    $memberName = $this->splitNameFromFilters($filters['member_name']);
    //    $title = $filters['entry_title'];
    //    $companyAuthor = $filters['company_author'];
    //    $entryCompany = $filters['entry_company'];
    //    $entryType = $filters['entry_type'];
    //    $clickedOn = $filters['clicked_on'];
    //    $topics = $filters['topics'];
    //    $author = $this->splitNameFromFilters($filters['author']);

    //    if(in_array('date_from', $filters)){
    //         $dateFrom = $filters['date_from'];
    //         $dateTo = $filters['date_to'];
    //    }

    //    // Additional
    //    $consentNeeded = $filters['consent_needed'];
     

    //     $rows = (new craft\db\Query())
    //             ->select(['first_name', 
    //             'last_name', 'job_title',
    //             'company_name', 'email', 'phone','user_city',
    //             'user_country_id', 'title', 'type', 'author_first_name', 'author_last_name', 'companys_author_name',
    //             'click', 'created'])
    //             ->from('viewcount_s2nodeanalytics')
    //             ->where(['like', 'first_name', $memberName['first_name'], false])
    //             ->andWhere(['like', 'last_name', $memberName['last_name'], false])
    //             ->andWhere(['like', 'title', $title, false])
    //             ->andWhere(['like', 'companys_author_name', $companyAuthor, false])
    //             ->andWhere(['like', 'company_name', $entryCompany, false])
    //             ->andWhere(['type' => $entryType])
    //     //        ->andWhere(['clicked_on' => $clickedOn])
    //     //       ->andWhere(['topics' => $topics])
    //             ->andWhere(['like', 'author_first_name', $author['first_name'], false])
    //        //     ->andWhere(['like', 'author_last_name', $author['last_name'], false])
    //        //     ->andWhere(['consent_needed' => $consentNeeded])
    //        //     ->andWhere(['created' => $dateFrom])
    //        //     ->andWhere(['created' <= $dateTo])
    //             ->all();

    //     return $rows;
    // }


}
