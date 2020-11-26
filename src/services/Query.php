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

    public function filterByMemberName(string $filter)
    {

        $member = explode(' ', $filter);

        $firstName = $member[0];
        $lastName = $member[1];

        $rows = (new craft\db\Query())
                ->select(['first_name', 
                'last_name', 'job_title',
                'company_name', 'email', 'phone','user_city',
                'user_country_id', 'title', 'type', 'author_first_name', 'author_last_name', 'companys_author_name',
                'click', 'created'])
                ->from('viewcount_s2nodeanalytics')
                ->where(['first_name' => $firstName, 'last_name' => $lastName])
                ->all();

        return $rows;
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

}
