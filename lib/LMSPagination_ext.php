<?php

/*
 * \class LMSPagination_ext
 * \brief Class responsibility for page numbering.
 */
class LMSPagination_ext
{

    /*
     * \brief Number of items per page.
     */
    private $perPage = null;

    /*
     * \brief Total number of items.
     */
    private $items = 0;

    /*
     * \brief Range (limit) for page numbering.
     */
    private $range = null;

    /*
     * \brief Number of current selected page.
     */
    private $currentPage = null;

    /*
     * \brief $_GET query with deleted page variable.
     */
    private $link = '';

    public function __construct()
    {
        $tmp = $_GET;

        if (!empty($tmp['page'])) {
            unset($tmp['page']);
        }

        $this->link = http_build_query($tmp);
    }

    /*
     * \brief Return link to use in page numbering.
     *
     * \return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /*
     * \brief Method set number of items on single page.
     *
     * \param $v int number of items on single page.
     */
    public function setItemsPerPage($v)
    {
        $v = (int) $v;

        if ($v > 0) {
            $this->perPage = $v;
        }
    }

    /*
     * \brief Method return number of items on single page.
     *
     * \return int
     */
    public function getItemsPerPage()
    {
        return $this->perPage;
    }

    /*
     * \brief Method set total number of items.
     *
     * \param $v int
     */
    public function setItemsCount($v)
    {
        $v = (int) $v;

        if ($v > 0) {
            $this->items = $v;
        }
    }

    /*
     * \brief Method return number of items.
     *
     * \return int
     */
    public function getItemsCount()
    {
        return $this->items;
    }

    /*
     * \brief Method set range (limit) for page numbers.
     * If you have many pages or don't want see them all set this.
     *
     * \param $v int range for left and right side to display page numbers.
     */
    public function setRange($v)
    {
        $v = (int) $v;

        if ($v > 0) {
            $this->range = $v;
        }
    }

    /*
     * \brief Method set current page.
     *
     * \param $v int
     */
    public function setCurrentPage($v)
    {
        $v = (int) $v;

        if ($v > 0) {
            $this->currentPage = $v;
        }
    }

    /*
     * \brief Method return current selected page.
     *
     * \return int
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    /*
     * \brief Main logic establish page numbers to display.
     *
     * \return array
     */
    public function getPages()
    {
        $pages        = array();
        $pages_count  = ceil($this->items/$this->perPage);
        $range        = ($this->range)       ? $this->range       : 0;
        $current_page = ($this->currentPage) ? $this->currentPage : 1;

        for ($i=1; $i<=$pages_count; ++$i) {
            $flag = ($i==$current_page) ? 'current' : 'normal';
               $pages[] = array('page_num'=>$i, 'flag'=>$flag);
        }

        if ($range && isset($pages[$current_page-1])) {
            $current = reset($pages);
            while ($current !== false && $current['page_num'] != $current_page) {
                $current = next($pages);
            }

            $steps = 2*$range - count(array_slice($pages, $current_page, $range));
            $left  = array();
            while (($prev = prev($pages)) !== false && $steps>0) {
                array_unshift($left, $prev);
                --$steps;
            }

            $right = array_slice($pages, $current_page, 2*$range-count($left));
            $pages = array_merge($left, array($pages[$current_page-1]), $right);
        }

        $result['pages'] = $pages;
        $result['prev']  = ($current_page > 1)            ? $current_page - 1 : 0;
        $result['next']  = ($pages_count > $current_page) ? $current_page + 1 : 0;

        return $result;
    }
}
