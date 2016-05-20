<?php

namespace Test;

use mysqli;

use Tornado\Application\Tornado;

use Test\DataSift\ApplicationBuilder;

/**
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class DatabaseTest extends \PHPUnit_Framework_TestCase
{
    use ApplicationBuilder;

    /**
     * MySQLi object
     *
     * @var \mysqli
     */
    protected $dbconn;

    /**
     * Test database name
     *
     * @var string
     */
    protected $database;

    public function setUp()
    {
        $this->buildApplication(Tornado::class, '/src/config/tornado');

        // Build the container
        $container = $this->container;

        $this->database = $container->getParameter('db.database');

        // connect to the database
        try {
            $this->dbconn = new mysqli(
                $container->getParameter('db.host'),
                $container->getParameter('db.username'),
                $container->getParameter('db.password'),
                $this->database,
                $container->getParameter('db.port'),
                $container->getParameter('db.unix_socket')
            );
        } catch (\Exception $e) {
            $this->markTestSkipped();
            return;
        }

        $this->assertNull($this->dbconn->connect_error);

        // populate the database
        $this->executeSQL($this->rootDir .'/database/master.sql');
    }

    public function tearDown()
    {
        // if there was an error connecting, then this isnt an object
        if ($this->dbconn) {
            $this->dbconn->close();
        }
    }

    public function testSampleData()
    {
        // prepare the SQL
        $this->executeSQL($this->rootDir .'/database/sample_data/fixtures.sql');
        $result = $this->dbconn->query(
            'SELECT * FROM `'.$this->database.'`.`chart`'
        );
        $this->assertTrue($result->num_rows > 1);
    }

    /**
     * Parse an SQL text document and returns an array containing a single line SQL query on each item.
     *
     * @param string $sqlfile The SQL file to parse
     *
     * @return array contaning one SQL query on each entry
     */
    private function executeSQL($sqlfile)
    {
        // prepare the SQL
        $sql = str_replace('`tornado`', '`'.$this->database.'`', file_get_contents($sqlfile));
        $queries = $this->getQueriesFromSQL($sql);
        foreach ($queries as $query) {
            $this->assertTrue($this->dbconn->query($query), $query);
        }
    }

    /**
     * Parse an SQL text document and returns an array containing a single line SQL query on each item.
     *
     * @param string $sql SQL text to parse.
     *
     * @return array contaning one SQL query on each entry
     */
    private function getQueriesFromSQL($sql)
    {
        // array to be returned
        $queries = array();
        // remove CR
        $sql = str_replace("\r", '', $sql);
        //prepare string for replacements
        $sql = "\n".$sql."\n";
        // remove comments (/* ... */)
        $sql = preg_replace("/\/\*([^\*]*)\*\//si", ' ', $sql);
        // remove comments (lines starting with '#')
        $sql = preg_replace("/\n([\s]*)\#([^\n]*)/si", '', $sql);
        // remove comments (lines starting with '--')
        $sql = preg_replace("/\n([\s]*)\-\-([^\n]*)/si", '', $sql);
        // mark valid new lines
        $sql = preg_replace("/;([\s]*)\n/si", ";\r", $sql);
        // replace new lines with a space character
        $sql = str_replace("\n", ' ', $sql);
        // remove last ";\r"
        $sql = preg_replace("/(;\r)$/si", '', $sql);
        // split sql string into single line SQL statements
        $queries = explode(";\r", trim($sql));
        return $queries;
    }
}
