<?php

namespace Laraveltable\Table;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

class Table extends Model
{
    private $currentTable;
    private $query; // query
    private $byPage = 10; // limit
    private $page; // offset
    private $selects = [];
    private $orderBy;
    private $joins = [];
    private $direction;
    private $datas;
    private $columnSorted;
    private $columnDisplayed;
    private $nbResult;
    private $nbPage;
    private $search;
    private $linkShowed = 5;
    private $callbackData = [];
    private $callbackSearch = [];
    private $wheres= [];
    private $addShowColumn = true;
    private $addEditColumn = true;
    private $addDeleteColumn = true;
    private $link = '';
    private $debug = false;
    private $slugColumnName = "id";
    /**
     * @description init table name
     */
    public function __construct($currentTable)
    {
        parent::__construct();
        $this->currentTable = $currentTable;
    }

    /**
     * @description init current page
     */
    public function initPage()
    {
        !empty($_GET['page']) ? $page = $_GET['page'] : $page = null;
        (string)(int)$page == $page && $page > 0 ? $this->page = (int)$page : $this->page = 1;
    }
    /**
     * @description init order by
     */
    public function initOrderBy()
    {
        !empty($_GET['orderby']) ? $orderBy = $_GET['orderby'] : $orderBy = null;
        if (in_array($orderBy, $this->columnSorted)) {
            $this->orderBy = $orderBy;
            $this->initDirection();
            $this->query = $this->query->orderBy($this->orderBy, $this->direction);
        }
    }
    /**
     * @description init direction table(asc or desc)
     */
    public function initDirection()
    {
        !empty($_GET['direction']) ? $direction = $_GET['direction'] : $direction = "asc";
        $direction == "asc" ? $this->direction = 'asc' : $this->direction = 'desc';
    }
    /**
     * @description init join
     */
    public function initJoin()
    {
        foreach ($this->joins as $join) {
            $table = $join[0];
            $relation1 = $join[1];
            $symbol = $join[2];
            $relation2 = $join[3];
            $this->query = $this->query->leftJoin($table, $relation1, $symbol, $relation2);
        }
    }
    /**
     * @description init where
     */
    public function initWhere()
    {
        if (!empty($this->wheres)) {
            foreach ($this->wheres as $where) {
                $this->query = $this->query->where($where[0], $where[1], $where[2]);
            }
        }
    }
    /**
     * @description init search from get param
     */
    public function initSearch()
    {
        // get search field
        $this->search = !empty(Input::get('search')) ? Input::get('search') : null;
        //prepare query
        if (!empty($this->search)) {
            $this->query = $this->query->where(function ($query) {
                foreach ($this->columnDisplayed as $k => $v) {
                    $query->orWhere($k, 'like', '%' . /*$this->search*/
                        $this->callbackSearch($k, $this->search) . '%');
                }
            });
        }
    }
    /**
     * @description init nb result
     */
    public function initNbResults()
    {
        $this->nbResult = $this->query->count();
    }
    /**
     * @description init nb page
     */
    public function initNbPages()
    {
        $this->nbPage = ceil($this->nbResult / $this->byPage);
    }
    /**
     * @description init limit
     */
    public function initLimit()
    {
        $this->query = $this->query->limit($this->byPage);
    }
    /**
     * @description init offset
     */
    public function initOffset()
    {
        $this->query = $this->query->offset($this->byPage * ($this->page - 1));
    }
    /**
     * @description init select
     */
    public function initSelect() {
        if(!empty($this->selects)) {
            $this->query = call_user_func_array(array($this->query, "select"), array($this->selects));
        } else {
            $this->query = $this->query->select("*");
        }

    }
    /**
     * @description excute query
     */
    private function executeQuery()
    {
        $this->query = DB::table($this->currentTable);
        $this->initSelect();
        $this->initPage();
        $this->initOrderBy();
        $this->initSearch();
        $this->initWhere();
        $this->initJoin();
        $this->initNbResults(); // ! important to do this before limit && offset
        $this->initNbPages(); // ! important to do this before limit && offset
        $this->initLimit();
        $this->initOffset();
        if($this->debug) {
            dd($this->query->toSql());
        }

        $this->datas = $this->query->get();
    }
    /**
     * @description prepare view
     */
    public function prepareView()
    {
        $this->executeQuery();
    }

    /**
     * @description get html table
     */
    public function getHtmlTable()
    {
        $html = '<table class="table table-responsive table-striped">';
        $html .= '<thead>';
        $html .= '<tr>';
        // header
        foreach ($this->columnDisplayed as $k => $v) {
            $orderByIcon = !empty($this->orderBy) && $this->orderBy == $k ?
                $this->direction == 'asc' ? '<span class="glyphicon glyphicon-arrow-up"></span>' : '<span class="glyphicon glyphicon-arrow-down"></span>' : ''; // class des fleches de direction
            $sortableClass = in_array($k, $this->columnSorted) ? 'sortable' : ''; // applique la class sortable en fonction de $this->columnSorted
            $html .= '<th class="' . $sortableClass . '" data-column="' . $k . '">' . $v . $orderByIcon . '</th>';
        }
        if ($this->addShowColumn) {
            $html .= '<th>Voir</th>';
        }
        if ($this->addEditColumn) {
            $html .= '<th>Modifier</th>';
        }
        if ($this->addDeleteColumn) {
            $html .= '<th>Supprimer</th>';
        }
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        //datas
        foreach ($this->datas as $data) {
            is_object($data) ? $data = get_object_vars($data) : $data = $data;

            $html .= '<tr>';
            foreach ($this->columnDisplayed as $k => $v) {
                if(is_string($data[$k])){
                    if(strlen($data[$k]) > 150)
                        $data[$k] = substr($data[$k] , 0, 149) . '...' ;
                }
                $html .= '<th>' . /*$data[$k]*/
                    $this->callbackData($k, $data[$k]) . '</th>';
            }
            // actions
            if ($this->addShowColumn) {
                $html .= '<th><a href="' . $this->link.'/'.$data[$this->slugColumnName] . '/show"><span class="glyphicon glyphicon-search" aria-hidden="true"></span></a></th>';
            }
            if ($this->addEditColumn) {
                $html .= '<th><a href="' . $this->link.'/'.$data[$this->slugColumnName].'/update'.'"><span class="glyphicon glyphicon-pencil" aria-hidden="true"></span></a></th>';
            }
            if ($this->addDeleteColumn) {
                $html .= '<th><a class=".delete-table-element" table-delete-id="'.$data[$this->slugColumnName].'"> <span class="glyphicon glyphicon-remove" aria-hidden="true"></span></a></th>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }

    /**
     * @description get html pagination
     */
    public function getHtmlPagination()
    {
        if ($this->nbPage < 2) {
            return '';
        }
        $goToFirstClass = $previousClass = $this->page == 1 ? "disabled" : "";
        $goToLastClass = $nextClass = $this->nbPage == $this->page ? "disabled" : "";

        $html = '<ul class="pagination">';
        $html .= '<li class="go-to-first ' . $goToFirstClass . '"><a class ="a-pagination" data-page="1"><<</a></li>';
        $html .= '<li class="previous ' . $previousClass . '"><a class ="a-pagination" data-page="' . ($this->page - 1) . '"><</a></li>';

        for ($i = $this->page - $this->linkShowed; $i <= $this->page + $this->linkShowed; $i++) {
            if ($i > 0 && $i <= $this->nbPage) {
                $active = $i == $this->page ? "active" : "";
                $html .= '<li class="' . $active . '"><a class ="a-pagination" data-page="' . $i . '">' . $i . '</a></li>';
            }
        }
        $html .= '<li class="next ' . $nextClass . '"><a class ="a-pagination" data-page="' . ($this->page + 1) . '">></a></li>';
        $html .= '<li class="go-to-last ' . $goToLastClass . '"><a class ="a-pagination" data-page="' . $this->nbPage . '">>></a></li>';

        $html .= '</ul>';

        return $html;
    }

    /**
     * @param String $buttonName
     * @return string
     * @description get html search
     */
    public function getHtmlSearch($buttonName)
    {
        $html = '<input type="text" class="search" id="search-input">';

        $html .= '<button type="button" class="btn btn-primary" id="search-button">' . $buttonName . '</button>';

        return $html;
    }

    /**
     * @param String $buttonName
     * @return string
     * @description get html button for reset search parameter in url
     */
    public function getHtmlSearchReset($buttonName)
    {
        $html = '<button type="button" class="btn btn-primary" id="reset-search-button">' . $buttonName . '</button>';

        return $html;
    }

    /**
     * @param String $columnName
     * @param Funtion $callback
     *
     * save callback function in an array
     */
    public function addCallBackData($columnName, $callback)
    {
        $this->callbackData[$columnName] = $callback;
    }

    public function addCallBackSearch($columnName, $callback)
    {
        $this->callbackSearch[$columnName] = $callback;
    }

    /**
     * @param $columnName
     * @param $data
     * @return mixed
     *
     * If callback exist for this table name, we call this callback
     * else return the default value
     */
    public function callbackData($columnName, $data)
    {
        if (!empty($this->callbackData[$columnName])) {
            return $this->callbackData[$columnName]($data);
        } else {
            return $data;
        }
    }
    /**
     * @param $columnName
     * @param $data
     * @return mixed
     *
     * If callback exist for this table name, we call this callback
     * else return the default value
     */
    public function callbackSearch($columnName, $data)
    {
        if (!empty($this->callbackSearch[$columnName])) {
            return $this->callbackSearch[$columnName]($data);
        } else {
            return $data;
        }
    }


    // -------------- GETTER && SETTER

    /**
     * @param Array $join
     * @description add join
     */
    public function addJoin($join)
    {
        $this->joins[] = $join;
    }

    /**
     * @param Array $where
     * @description add where
     */
    public function addWhere($where)
    {
        $this->wheres [] = $where;
    }

    /**
     * @param String $select
     * @decription add select
     */
    public function addSelect($select) {
        $this->selects [] = $select;
    }

    /**
     * @param string $property
     * @return mixed
     * @description magic getter
     */
    public function __get($property) {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }

    /**
     * @param string $property
     * @param mixed $value
     * @return $this
     * @description magic setter
     */
    public function __set($property, $value) {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        }

        return $this;
    }
}
