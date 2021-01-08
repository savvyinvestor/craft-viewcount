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

       $paperId = $filters['paper_id'];
       $memberId = $filters['member_id'];
       $companyId = $filters['company_id'];
       $memberName = $this->splitNameFromFilters($filters['member_name']);
       $title = $filters['entry_title'];
    //    $companyAuthor = $filters['author_company'];
    //    $memberCompany = $filters['member_company'];
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
                    'c.id as paper_id',
                    'content_users.id as member_id',
                    'content_authors.id as company_id',
                    'users_members.firstName as first_name', 
                    'users_members.lastName as last_name', 
                    'c.title as entry_title',
                    'content_users.field_userCompanyName as member_company',
                    'content_authors.field_userCompanyName as author_company',

                    'users_members.email as email',
                    'c.field_paperType as type',
                    'c.dateCreated as created',
                    'content_users.field_country as user_country',
                    'content_users.field_city as user_city',
                    'content_users.field_jobTitle as job_title',
                    'content_authors.field_consentNeeded as consent_needed', 
                    'users_authors.firstName as author_first_name', 
                    'users_authors.lastName as author_last_name',
                    'topics.title as topics'

                ])
                ->from('viewcount_viewlog')
                ->innerJoin('{{%content}} c', '[[viewcount_viewlog.elementId]] = [[c.elementId]]')
                ->innerJoin('{{%entries}}', '[[entries.id]] = [[c.elementId]]')
                ->innerJoin('{{%users}} users_members', '[[viewcount_viewlog.userId]] = [[users_members.id]]')
                ->innerJoin('{{%relations}}', '[[entries.id]] = [[relations.sourceId]]')
                ->innerJoin('{{%categories}}', '[[categories.id]] = [[relations.targetId]]')
                ->leftJoin('{{%content}} topics', '[[categories.id]] = [[topics.elementId]]')
                ->leftJoin('{{%content}} content_authors', '[[content_authors.elementId]] = [[entries.authorId]]')
                ->leftJoin('{{%users}} users_authors', '[[entries.authorId]] = [[users_authors.id]]')
                ->leftJoin('{{%content}} content_users', '[[content_users.elementId]] = [[users_members.id]]')

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

                ->where(['content_authors.id' => $companyId])
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
