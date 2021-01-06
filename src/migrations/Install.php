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

namespace doublesecretagency\viewcount\migrations;

use craft\db\Migration;

/**
 * Installation Migration
 * @since 1.0.0
 */
class Install extends Migration
{

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        // $this->dropTableIfExists('{{%viewcount_entryanalytics}}');
        $this->dropTableIfExists('{{%viewcount_elementtotals}}');
        $this->dropTableIfExists('{{%viewcount_viewlog}}');
        $this->dropTableIfExists('{{%viewcount_userhistories}}');
    }

    /**
     * Creates the tables.
     */
    protected function createTables()
    {
        // $this->createTable('{{%viewcount_entryanalytics}}', [
        //     'id'          => $this->primaryKey(),
        //     's2_nid'   => $this->integer()->notNull(),                 // s2
        //     'elementId' => $this->integer()->notNull(),             // s3
        //     'title'     => $this->string()->notNull(),
        //     'type'     => $this->string(),
        //     's2_uid'   => $this->integer()->notNull(),              // s2
        //     'uid' => $this->string(),                               // s3
        //     'first_name'     => $this->string(),
        //     'last_name'     => $this->string(),
        //     'job_title'     => $this->string(),
        //     'company_name'     => $this->string(),
        //     'email'     => $this->string(),
        //     'phone'     => $this->string(),
        //     'user_city'     => $this->string(),
        //     's2_user_country_id'   => $this->integer(),             // s2
        //     'user_country_id'   => $this->integer(),                // s3
        //     'click' => $this->boolean()->notNull(),
        //     'dateCreated' => $this->dateTime()->notNull(),
        //     'dateUpdated' => $this->dateTime()->notNull(),
        //     's2_author_company_nid'   => $this->integer(),             // s2
        //     'author_company_elementId'   => $this->integer(),       // s3
        //     'author_company_title' => $this->string(),
        //     's2_companys_author_uid'   => $this->integer(),         // s2
        //     'companys_author_uid'   => $this->integer(),            // s3
        //     'companys_author_name' => $this->string(),
        //     's2_node_companys_nid'   => $this->integer(),              // s2
        //     'entry_companys_elementId'   => $this->integer(),       // s3
        //     'entry_companys_title' => $this->string(),
        //     'author_first_name' => $this->string(),
        //     'author_last_name' => $this->string(),
        //     'created' => $this->integer()->notNull(),
        //     'consent_needed' => $this->boolean(),
        // ]);


        $this->createTable('{{%viewcount_elementtotals}}', [
            'id'          => $this->primaryKey(),
            'elementId'   => $this->integer()->notNull(),
            'viewKey'     => $this->string(),
            'viewTotal'   => $this->integer()->defaultValue(0),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid'         => $this->uid(),
        ]);
        $this->createTable('{{%viewcount_viewlog}}', [
            'id'          => $this->primaryKey(),
            'elementId'   => $this->integer()->notNull(),
            'viewKey'     => $this->string(),
            'userId'      => $this->integer(),
            'ipAddress'   => $this->string(),
            'userAgent'   => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid'         => $this->uid(),
        ]);
        $this->createTable('{{%viewcount_userhistories}}', [
            'id'          => $this->integer()->notNull(),
            'history'     => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid'         => $this->uid(),
            'PRIMARY KEY([[id]])',
        ]);
    }

    /**
     * Creates the indexes.
     */
    protected function createIndexes()
    {
        // $this->createIndex(null, '{{%viewcount_entryanalytics}}', ['nid']);
        $this->createIndex(null, '{{%viewcount_elementtotals}}', ['elementId']);
        $this->createIndex(null, '{{%viewcount_viewlog}}',       ['elementId']);
    }

    /**
     * Adds the foreign keys.
     *
     * @return void
     */
    protected function addForeignKeys()
    {
        $this->addForeignKey(null, '{{%viewcount_elementtotals}}', ['elementId'], '{{%elements}}', ['id'], 'CASCADE');
        $this->addForeignKey(null, '{{%viewcount_viewlog}}',       ['elementId'], '{{%elements}}', ['id'], 'CASCADE');
        $this->addForeignKey(null, '{{%viewcount_userhistories}}', ['id'],        '{{%users}}',    ['id'], 'CASCADE');
    }

}
