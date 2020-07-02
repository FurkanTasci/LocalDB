<?php

namespace LocalDB;

use Exception;
use stdClass;

/**
 * @author Furkan Tasci <kontakt@furkantasci.de>
 * @package LocalDB\LocalDB
 * 
 * 
 * ToDo: 
 *  - use Repository Design Patterm 
 *  - 
 */

class LocalDB
{
    /**
     * @var string
     */
    protected static $path;

    /**
     * @var string
     */
    protected static $table;

    /**
     * @var integer
     */
    private $anchor;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var array
     */
    protected $select;

    /**
     * @var array
     */
    public $columns;

    /**
     * @var string
     */
    protected $merge;

    /**
     * Contructor 
     * 
     * @param string $path
     * @param int $mode Default 0775
     */
    public function __construct(string $path, int $mode = 0775)
    { 
        static::$path = (string) rtrim($path, '/');
        static::$path = $path;

        static::directory(static::$path, $mode);
    }

    /**
     * @param string $path
     * @param integer $mode
     * 
     * @return void
     */
    private static function directory(string $path, int $mode) 
    {
        if (!is_dir($path)) 
            mkdir($path, $mode, true);
    }

    /**
     * @return string $table
     */
    protected static function getTable() 
    {
        return static::$table;
    }

    /**
     * @param string $table
     */
    protected static function setTable(string $table) 
    {
        static::$table = sprintf('%s/%s.json', static::$path, str_replace('.json', '', $table));
    }

    /**
     * @return array
     */
    private function getData() 
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    private function setData(array $data) 
    {
        $this->data = $data;
    }

    /**
     * @param int $anchor
     */
    private function setAnchor(int $anchor) 
    {
        $this->anchor = $anchor;
    }

    /**
     * @return array|mixed
     */
    private function getAnchor() 
    {
        return $this->anchor;
    }

    /**
     * Saves the table in to $path Folder
     */
    private function save() 
    {
        $f = fopen(static::getTable(), 'w+');
        fwrite($f, (!$this->getData() ? '[]' : json_encode($this->getData(), JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)));
        fclose($f);
    }

    /**
     * Checks whether the table exists and whether the table is correctly formatted
     */
    public function exists() 
    {  
        if (!file_exists(static::getTable()))
            $this->save();

        $json = file_get_contents(static::getTable());
        $json = json_decode($json);

        if (!is_array($json) && is_object($json)) {
            throw new Exception('the json object must contain an array.' );
			return false;
        } else {
            return true;
        }
    }

    /**
     * @param string $table
     * @return object $this
     */
    public function create($table) 
    {

        static::setTable($table);

        if ($this->exists()) 
            $this->setData((array) json_decode(file_get_contents(static::getTable())));

        return $this;
    }

    /**
     * @param string table
     * @param string data
     *
     * @throws Exception Columns must match as of the first row
     *
     * @return int
     */
    public function insert($table, $data) 
    {
        $this->create($table);

        if (!empty($this->content) && array_diff_key($data, (array) $this->content[0])) {
			throw new Exception('Columns must match as of the first row');
		} else {
            $this->data[] = (object) $data;
            // [(count($this->data) - 1)];
			$this->setAnchor(count($this->getData()) - 1);
			$this->save();
        }

        return $this->anchor;
    }

    /**
     * @param string $table
     * 
     * @return object $this
     */
    public function from($table) 
    {
        static::setTable($table);

        if ($this->exists()) {
            $data = json_decode(file_get_contents(static::getTable()));
            $this->setData($data);
        }

        return $this;
    }

    /**
     * @param array $columns
     * @param string default 'OR'
     * 
     * @return object $this
     */
    public function where(array $columns, $merge = 'OR') 
    {
        // change merge to statement
        $this->columns = $columns;
        $this->merge = $merge;

        return $this;
    }

    /**
     * @param $column
     * 
     * @return object $this
     */
    public function select(string $column) 
    {
        $explode = explode(',', $column);
        $this->select = array_map('trim', $explode);

        return $this;
    }

    /**
     * @return array
     */
    public function get() 
    {
        foreach ($this->data as $id => $row) {
			foreach ((array) $row as $key => $val) {
				if (in_array($key, $this->select)) 
					$res[$id][$key] = $val;
			}
		}

		return empty($res) ? [] : $res;
    }

    /**
     * @return array|bool 
     */
    private function iteratorColumn() 
    {
        if ($this->merge == "AND") {
            return $this->iteratorRow();
        }

        $arr = array_filter($this->getData(), function($row, $index) {
            // Computes the intersection of arrays with additional index check, compares data and indexes by separate callback functions
            if (array_uintersect_uassoc((array) $row, $this->columns, "strcasecmp", "strcasecmp")) {
                $this->anchor[] = $index;
                // $this->setAnchor($index);
                return true;
            }

            return false;
        }, ARRAY_FILTER_USE_BOTH);

        // Convert an object to associative Array
        return json_decode(json_encode($arr), true);
    }

    /**
     * @return array
     */
    public function iteratorRow() 
    {
        $arr = [];

        foreach ($this->getData() as $index => $row) {
            if (!array_udiff_uassoc($this->columns, (array) $row, "strcasecmp", "strcasecmp")) {
                $arr[] = $row;        
                $this->setAnchor($index);
            }
        }

        return $arr;
    }

    /**
     * @return object $this
     */
    public function run() 
    {        
        $this->content ?? $this->iteratorColumn() ?? $this->data;

        if (!empty($this->getAnchor()) && !empty($this->columns)) {       
            $data = array_filter($this->getData(), function($index) {
                return !in_array($index, (array) $this->getAnchor());
            }, ARRAY_FILTER_USE_KEY);

            $this->setData($data);

            $this->data = array_values($this->data);
            
        } else if(empty($this->columns) && empty($this->getAnchor())) {
            // clear all data 
            $this->data = [];
            // $this->setData([]);
        }

        $this->save();
        return $this;
    }

    /**
     * @param string $table
     * 
     * @return object $this
     */
    public function delete(string $table) 
    {
        $this->from($table);
        return $this;
    }
}   
?>