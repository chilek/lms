<?php

class LocationCache {

    // database connection instance
	private $DB                   = null;

    // cache array with key as LMS database ID
    private $city_by_id           = array();
    private $city_by_id_loaded    = false;

    // cache array with key as city ident
    private $city_by_ident        = array();
    private $city_by_ident_loaded = false;

    private $city_with_sections_by_id = array();

    // location buildings cache
    private $buildings            = array();

    // location street cache
    private $streets              = array();
    private $streets_loaded       = false;

    // load policy types
    const LOAD_FULL = 'full';
    const LOAD_ONE  = 'one';

    // choosen load policy
    private $load_policy = self::LOAD_ONE;

    /*!
     * \brief Class constructor.
     *
     * \param string load policy
     */
	public function __construct( $load_policy = null ) {
		$this->DB = LMSDB::getInstance();

		if ( $load_policy !== null ) {
			$this->setLoadPolicy( $load_policy );
		}
	}

	/*!
	 * \brief Set loading policy.
	 *
	 * \param $v
	 * LOAD_FULL 'full':
	 * After first get method use will be downloaded all rows from table and
	 * save to array. All next asks about will be returned from cache.
	 * Use this option if you need to read many rows and your database is't
	 * big.
	 *
	 * LOAD_ONE 'one':
	 * Similar to REAL_TIME but remember only last searched value in cache.
	 * Use this option when you need to read many rows but get full table in
	 * one time can exceed a memory limit.
	 */
	public function setLoadPolicy( $v ) {
		$v = trim( $v );
		$v = strtolower( $v );

		switch ( $v ) {
			case self::LOAD_FULL:
			case self::LOAD_ONE:
				$this->load_policy = $v;
			break;

			default:
				throw new Exception('Illegal state exception. Incorrect load policy value.');
		}
	}

	/*!
	 * \brief Return row from location_cities by id.
	 * equals to:
	 * SELECT * FROM location_cities WHERE id = x;
	 *
	 * \param int   $id row id
	 * \param array if record was found
	 * \param null  if record wasn't found
	 */
	public function getCityById( $id ) {

		switch ( $this->load_policy ) {

			case self::LOAD_FULL:
				if ( $this->city_by_id_loaded == false ) {
					$this->initCityByIdCache();
				}

			    if ( isset( $this->city_by_id[ $id ] ) ) {
			        return $this->city_by_id[ $id ];
			    } else {
			    	return null;
			    }
			break;

			case self::LOAD_ONE:
				if ( isset( $this->city_by_id[ $id ] ) ) {
			        return $this->city_by_id[ $id ];
			    } else {
					$this->city_by_id = $this->DB->GetAllByKey(
						'SELECT lc.id, ' . $this->DB->Concat('ls.ident', "'|'", 'ld.ident', "'|'", 'lb.ident', "'|'", 'lb.type', "'|'", 'lc.ident') . ' AS terc_simc,
							lc.cityid FROM location_cities lc
						JOIN location_boroughs lb ON lb.id = lc.boroughid
						JOIN location_districts ld ON ld.id = lb.districtid
						JOIN location_states ls ON ls.id = ld.stateid
						WHERE lc.id = ?',
						'id', array( $id ));

			    	if ( isset( $this->city_by_id[ $id ] ) ) {
			        	return $this->city_by_id[ $id ];
			    	}

			    	return null;
			    }
			break;
		}
	}

	/*!
	 * \brief Return row from location_cities by terc and ident.
	 * equals to:
	 * SELECT * FROM location_cities WHERE ident like 'x';
	 *
	 * \param int   $terc terc ident
	 * \param int   $simc city row ident
	 * \param array if record was found
	 * \param null  if record wasn't found
	 */
	public function getCityByIdent($terc, $simc) {
		switch ( $this->load_policy ) {
			case self::LOAD_FULL:
				if ( $this->city_by_ident_loaded == false ) {
					$this->initCityByIdentCache();
				}

			    if ( isset( $this->city_by_ident[ $terc . '|' . $simc ] ) ) {
			        return $this->city_by_ident[ $terc . '|' . $simc ];
			    } else {
			    	return null;
			    }
			break;

			case self::LOAD_ONE:
				if ( isset( $this->city_by_ident[ $terc . '|' . $simc ] ) ) {
			        return $this->city_by_ident[ $terc . '|' . $simc ];
			    } else {
					$this->city_by_ident = $this->DB->GetAllByKey(
						'SELECT lc.id, ' . $this->DB->Concat('ls.ident', 'ld.ident', 'lb.ident', 'lb.type', "'|'", 'lc.ident') . ' AS terc_simc,
							lc.cityid FROM location_cities lc
						JOIN location_boroughs lb ON lb.id = lc.boroughid
						JOIN location_districts ld ON ld.id = lb.districtid
						JOIN location_states ls ON ls.id = ld.stateid
						WHERE lc.ident = ?',
						'terc_simc', array( (string) $simc ));

			    	if ( isset( $this->city_by_ident[ $terc . '|' . $simc ] ) ) {
			        	return $this->city_by_ident[ $terc . '|' . $simc ];
			    	} else {
				    	return null;
				    }
			    }
			break;
		}
	}

	/*!
	 * \brief Return row from location streets by cityid and ident.
	 * Equals to:
	 * SELECT * FROM location_streets WHERE ident like 'x';
	 *
	 * \param $cityid city id in database
	 * \param $ulic  street ident in database
	 * \param array  if record was found
	 * \param null   if record wasn't found
	 */
	public function getStreetByIdent( $cityid, $ulic ) {
	    switch ( $this->load_policy ) {
	    	case self::LOAD_FULL:
				if ( $this->streets_loaded == false ) {
					$this->streets = $this->DB->getAllByKey('SELECT id, ' . $this->DB->Concat('cityid', "'|'", 'ident') . ' AS cityid_ident,
						ident FROM location_streets', 'cityid_ident');

					foreach ($this->city_with_sections_by_id as $city_id => &$city)
						$city['streets'] = $this->DB->getAllByKey('SELECT id, ' . $this->DB->Concat('cityid', "'|'", 'ident') . ' AS cityid_ident,
							ident FROM location_streets WHERE cityid IN (' . implode(',', $this->city_with_sections_by_id[$city_id]['citysections'])
							. ')', 'ident');
					unset($city);

					$this->streets_loaded = true;
				}

				if (isset($this->city_with_sections_by_id[$cityid])) {
					if (isset($this->city_with_sections_by_id[$cityid]['streets'][$ulic]))
						return $this->city_with_sections_by_id[$cityid]['streets'][$ulic];
					else
						return null;
				} elseif ( isset($this->streets[$cityid . '|' . $ulic]) ) {
					return $this->streets[$cityid . '|' . $ulic];
				} else {
					return null;
				}
	    	break;

			case self::LOAD_ONE:
				if ( !isset($this->streets[$cityid . '|' . $ulic]) ) {
					$this->streets = $this->DB->getAllByKey('SELECT id, ' . $this->DB->Concat('cityid', "'|'", 'ident') . ' AS cityid_ident,
						ident FROM location_streets WHERE cityid = ?', 'cityid_ident', array( $cityid ));
				}

				if (isset($this->city_with_sections_by_id[$cityid]) && !isset($this->city_with_sections_by_id[$cityid]['streets'])) {
					$this->city_with_sections_by_id[$cityid]['streets'] =
						$this->DB->getAllByKey('SELECT id, ' . $this->DB->Concat('cityid', "'|'", 'ident') . ' AS cityid_ident,
							ident FROM location_streets WHERE cityid IN (' . implode(',', $this->city_with_sections_by_id[$cityid]['citysections'])
							. ')', 'ident');
				}

				if (isset($this->city_with_sections_by_id[$cityid])) {
					if (isset($this->city_with_sections_by_id[$cityid]['streets'][$ulic]))
						return $this->city_with_sections_by_id[$cityid]['streets'][$ulic];
					else
						return null;
				} elseif ( isset($this->streets[$cityid . '|' . $ulic]) ) {
					return $this->streets[$cityid . '|' . $ulic];
				} else {
					return null;
				}
		    break;
		}
	}

	/*!
	 * \brief Check if building exists in database.
	 *
	 * \param $cityid
	 * \param $streetid
	 * \param $building_num
	 * \return array with building fields
	 * \return false if not found
	 */
	public function buildingExists( $cityid, $streetid, $building_num ) {
		if ( !isset($this->buildings[ $cityid ]) ) {
			$tmp = $this->DB->GetAllByKey("SELECT (" . $this->DB->Concat('city_id', "'|'", "(CASE WHEN street_id IS NULL THEN 0 ELSE street_id END)", "'|'", 'building_num') . ")
												AS lms_building_key,
											longitude, latitude, id
											FROM location_buildings lb
											WHERE city_id = ?", 'lms_building_key', array($cityid));

			$this->buildings = null;
			$this->buildings[ $cityid ] = $tmp;
		}

		$key = $cityid . '|' . $streetid . '|' . $building_num;

	    if ( isset($this->buildings[ $cityid ][ $key ]) ) {
	    	return $this->buildings[ $cityid ][ $key ];
	    } else {
	    	return false;
	    }
	}

	private function initCityWithSections() {
		$this->city_with_sections_by_id = $this->DB->GetAllByKey("SELECT lb2.cityid, lb2.cityname AS cityname, lb2.cityident AS cityident,
				(" . $this->DB->GroupConcat('lc.id', ',', true) . ") AS citysections
			FROM location_boroughs lb
			JOIN location_cities lc ON lc.boroughid = lb.id
			JOIN (SELECT lb.id, lb.districtid, lc.id AS cityid, lc.name AS cityname, lc.ident AS cityident
				FROM location_boroughs lb
				JOIN location_cities lc ON lc.boroughid = lb.id
				WHERE lb.type = 1
			) lb2 ON lb2.districtid = lb.districtid
			WHERE lb.type = 8 OR lb.type = 9
			GROUP BY lb2.cityid, lb2.cityname, lb2.cityident", 'cityid');
		if (empty($this->city_with_sections_by_id))
			return array();
		foreach ($this->city_with_sections_by_id as &$city)
			$city['citysections'] = explode(',', $city['citysections']);
		unset($city);
	}

    /*
     * \brief Create cache array or try imitate from other cache file.
     */
    private function initCityByIdentCache() {

    	if ( $this->city_by_id_loaded == true ) {
            foreach ($this->city_by_id as $v) {
                $this->city_by_ident[ $v['terc_simc'] ] = $v;
            }
		} else {
			$this->city_by_ident = $this->DB->GetAllByKey('SELECT lc.id, '
					. $this->DB->Concat('ls.ident', 'ld.ident', 'lb.ident', 'lb.type', "'|'", 'lc.ident')
					. ' AS terc_simc, lc.cityid FROM location_cities lc
				JOIN location_boroughs lb ON lb.id = lc.boroughid
				JOIN location_districts ld ON ld.id = lb.districtid
				JOIN location_states ls ON ls.id = ld.stateid',
				'terc_simc');
			$this->initCityWithSections();
		}

    	$this->city_by_ident_loaded = true;
    }

    /*
     * \brief Create cache array or try imitate from other cache file.
     */
    private function initCityByIdCache() {

    	if ( $this->city_by_ident_loaded == true ) {
            foreach ($this->city_by_ident as $v) {
                $this->city_by_id[ $v['id'] ] = $v;
            }
		} else {
			$this->city_by_id = $this->DB->GetAllByKey('SELECT lc.id, '
					. $this->DB->Concat('ls.ident', "'|'", 'ld.ident', "'|'", 'lb.ident', "'|'", 'lb.type', "'|'", 'lc.ident')
					. ' AS terc_simc, lc.cityid FROM location_cities lc
				JOIN location_boroughs lb ON lb.id = lc.boroughid
				JOIN location_districts ld ON ld.id = lb.districtid
				JOIN location_states ls ON ls.id = ld.stateid',
				'id');
		}

    	$this->city_by_id_loaded = true;
    }
}

?>
