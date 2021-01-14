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
       $memberFirstName = $filters['member_first_name'];
       $memberLastName = $filters['member_last_name'];
       $authorFirstName = $filters['author_first_name'];
       $authorLastName = $filters['author_last_name'];
       $paperTitle = $filters['paper_title'];
       $authorCompany = $filters['author_company'];
       $memberCompany = $filters['member_company'];
       $entryType = $filters['entry_type'];
       $clickedOn = $filters['clicked_on'];
       $topics = $filters['topics'];

       if($filters['limit_by_daterange'] == '1'){
            $dateFrom = $filters['date_from'];
            $dateTo = $filters['date_to'];
       }else{
            $dateFrom = '';
            $dateTo = '';
       }

       // Additional
       $consentNeeded = $filters['consent_needed'];
     
       $rows = (new craft\db\Query())
                ->select([
                    'c.id as paper_id',
                    'content_members.id as member_id',
                    'content_authors.id as author_company_id',
                    'users_members.firstName as member_first_name', 
                    'users_members.lastName as member_last_name', 
                    'users_authors.firstName as author_first_name', 
                    'users_authors.lastName as author_last_name',
                    'c.title as paper_title',
                    'content_members.field_userCompanyName as member_company',
                    'content_author_companies.title as author_company',
                    'users_members.email as member_email',
                    'et.name as type',
                    'c.dateCreated as created',
                    'content_members.field_country as member_country',
                    'content_members.field_city as member_city',
                    'content_members.field_jobTitle as member_job_title',
                    'content_author_companies.field_consentNeeded as company_consent_needed', 
                    'topics.title as topics'

                ])
                ->from('viewcount_viewlog')
                ->innerJoin('{{%content}} c', '[[viewcount_viewlog.elementId]] = [[c.elementId]]')
                ->innerJoin('{{%entries}} e', '[[e.id]] = [[c.elementId]]')
                ->innerJoin('{{%users}} users_members', '[[viewcount_viewlog.userId]] = [[users_members.id]]')
                ->innerJoin('{{%relations}}', '[[e.id]] = [[relations.sourceId]]')
                ->innerJoin('{{%categories}}', '[[categories.id]] = [[relations.targetId]]')
                ->innerJoin('{{%entrytypes}} et', '[[e.typeId]] = [[et.id]]')
                ->leftJoin('{{%content}} topics', '[[categories.id]] = [[topics.elementId]]')
                ->leftJoin('{{%content}} content_authors', '[[content_authors.elementId]] = [[e.authorId]]')
                ->leftJoin('{{%users}} users_authors', '[[e.authorId]] = [[users_authors.id]]')
                ->leftJoin('{{%content}} content_members', '[[content_members.elementId]] = [[users_members.id]]')
                ->leftJoin('{{%content}} content_author_companies', '[[content_author_companies.title]] = [[content_authors.field_userCompanyName]]')
                ->filterWhere(['c.id' => $paperId])      // Paper ID
                ->andFilterWhere(['content_members.id' => $memberId])   // Member ID
                ->andFilterWhere(['content_authors.id' => $companyId])   // Company ID
                ->andFilterWhere(['users_members.firstName' => $memberFirstName])   // Member first name
                ->andFilterWhere(['users_members.lastName' => $memberLastName])   // Member last name
                ->andFilterWhere(['users_authors.firstName' => $authorFirstName])   // Author first name
                ->andFilterWhere(['users_authors.lastName' => $authorLastName])   // Author last name
                ->andFilterWhere(['c.title' => $paperTitle])   // Paper title
                ->andFilterWhere(['content_authors.field_userCompanyName' => $authorCompany])   // Author company
                ->andFilterWhere(['content_members.field_userCompanyName' => $memberCompany])  // Member company
                // ->andFilterWhere(['clicked_on' => $clickedOn]) // Clicked on
                ->andFilterWhere(['in', 'topics.title', $topics])    // Topics
                ->andFilterWhere(['e.typeId' => $entryType])   // Entry type
                ->andFilterWhere(['content_authors.field_consentNeeded' => $consentNeeded])   // Consent needed
                ->andFilterWhere(['>', 'UNIX_TIMESTAMP(c.dateCreated)', $dateFrom])
                ->andFilterWhere(['<', 'UNIX_TIMESTAMP(c.dateCreated)', $dateTo])
                ->all();

        return $rows;
    }



    /*
    public function filterResults(array $filters)
    {

       $paperId = $filters['paper_id'];

        $rows = (new craft\db\Query())
                ->select([
                    'c.id as paper_id',
                    'e.id as entry_id'

                ])
                ->from('viewcount_viewlog')
                ->innerJoin('{{%content}} c', '[[viewcount_viewlog.elementId]] = [[c.elementId]]')
                ->innerJoin('{{%entries}} e', '[[e.id]] = [[c.elementId]]')
               

                // ->where([c.id => $paperId])      // Paper ID
                // ->andWhere([content_members.id => $memberId])   // Member ID
                // ->andWhere([content_authors.id => $companyId])   // Company ID
                // ->andWhere([users_members.firstName => $memberFirstName])   // Member first name
                // ->andWhere([users_members.lastName => $memberLastName])   // Member last name
                // ->andWhere([users_authors.firstName => $authorFirstName])   // Author first name
                // ->andWhere([users_authors.lastName => $authorLastName])   // Author last name
                // ->andWhere([c.title => $paperTitle])   // Paper title
                // ->andWhere([content_authors.field_userCompanyName => $authorCompany])   // Author company
                // ->andWhere(['content_members.field_userCompanyName' => $memberCompany])  // Member company
                // ->andWhere(['type' => $entryType])   // Entry type
                // ->andWhere(['clicked_on' => $clickedOn]) // Clicked on
                // ->andWhere(['topics' => $topics])    // Topics
                // ->andWhere([])   // Consent needed
                // ->andWhere(['created' => $dateFrom])
                // ->andWhere(['created' <= $dateTo])
                // ->all();

                ->where(['e.typeId' => 2])
                ->all();

        return $rows;
    }
*/

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
