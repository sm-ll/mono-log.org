<?php
class MemberSet
{
    private $members = array();
    private $prepared = false;
    private $supplemented = false;
    
    
    /**
     * Create a MemberSet
     * 
     * @param array  $members  Member array as starting data
     * @return MemberSet
     */
    public function __construct($members)
    {
        $this->members = $this->makeUnique($members);
    }
    
    
    
    // helper methods
    // ------------------------------------------------------------------------
    
    /**
     * Gets a count of the users contained in this set
     *
     * @return int
     */
    public function count()
    {
        return count($this->members);
    }

    
    /**
     * Ensures that the given user array is unique
     *
     * @param array  $members  Users to loop through
     * @return array
     */
    private function makeUnique($members)
    {
        // users isn't an array? no thanks
        if (!is_array($members)) {
            return array();
        }

        // record list of known usernames
        $usernames = array();

        foreach ($members as $username => $data) {
            if (in_array($username, $usernames)) {
                unset($members[$username]);
                continue;
            }
            
            // set username as part of the member info
            $members[$username]['username'] = $username;

            array_push($usernames, $username);
        }

        return $members;
    }


    
    // filter, sort, limit, and paginate
    // ------------------------------------------------------------------------
    
    /**
     * Filters the current users based on the filters given
     * 
     * @param array  $filters  Filters to apply
     * @return void
     */
    public function filter($filters)
    {
        $filters = Helper::ensureArray($filters);
        
        // nothing to filter, abort
        if (!$this->count()) {
            return;
        }
        
        $roles = null;
        $conditions = null;
        $where = null;
        
        // standardize filters
        $given_filters = $filters;
        $filters = array(
            'role'        => (isset($given_filters['role']))       ? $given_filters['role']       : null,
            'conditions'  => (isset($given_filters['conditions'])) ? $given_filters['conditions'] : null,
            'where'       => (isset($given_filters['where']))      ? $given_filters['where']      : null
        );
        
        // determine filters
        if ($filters['role']) {
            $roles = Parse::pipeList($filters['role']);
        }
        
        if ($filters['conditions']) {
            $conditions = Parse::conditions($filters['conditions']);
        }
        
        if ($filters['where']) {
            $where = $filters['where'];
        }
        
        // run filters
        foreach ($this->members as $username => $data) {
            if ($roles) {
                $found = false;
                foreach ($roles as $role) {
                    if (in_array($role, $data['roles'])) {
                        $found = true;
                        break;
                    }
                }
                
                if (!$found) {
                    unset($this->members[$username]);
                    continue;
                }
            }

            if ($where && !(bool) Parse::template("{{ if " . $where . " }}1{{ else }}0{{ endif }}", $data)) {
                unset($this->members[$username]);
                continue;
            }
            
            if ($conditions) {
                foreach ($conditions as $field => $instructions) {
                    try {
                        // are we looking for existence?
                        if ($instructions['kind'] === "existence") {
                            if ($instructions['type'] === "has") {
                                if (!isset($data[$field]) || !$data[$field]) {
                                    throw new Exception("Does not fit condition");
                                }
                            } elseif ($instructions['type'] === "lacks") {
                                if (isset($data[$field]) && $data[$field]) {
                                    throw new Exception("Does not fit condition");
                                }
                            } else {
                                throw new Exception("Unknown existence type");
                            }

                        // are we looking for a comparison?
                        } elseif ($instructions['kind'] === "comparison") {
                            if (!isset($data[$field])) {
                                $field = false;
                                $values = null;
                            } else {
                                $field  = (is_array($data[$field]))          ? array_map('strtolower', $data[$field])          : strtolower($data[$field]);
                                $values = (is_array($instructions['value'])) ? array_map('strtolower', $instructions['value']) : strtolower($instructions['value']);
                            }

                            // convert boolean-like statements to boolean values
                            if (is_array($values)) {
                                foreach ($values as $item => $value) {
                                    if ($value == "true" || $value == "yes") {
                                        $values[$item] = true;
                                    } elseif ($value == "false" || $value == "no") {
                                        $values[$item] = false;
                                    }
                                }
                            } else {
                                if ($values == "true" || $values == "yes") {
                                    $values = true;
                                } elseif ($values == "false" || $values == "no") {
                                    $values = false;
                                }
                            }

                            // equal comparisons
                            if ($instructions['type'] == "equal") {
                                // if this isn't set, it's not equal
                                if (!$field) {
                                    throw new Exception("Does not fit condition");
                                }

                                if (!is_array($field)) {
                                    if ($field != $values) {
                                        throw new Exception("Does not fit condition");
                                    }
                                } elseif (!in_array($values, $field)) {
                                    throw new Exception("Does not fit condition");
                                }

                            // greater than or equal to comparisons
                            } elseif ($instructions['type'] == "greater than or equal to") {
                                // if this isn't set, it's not greater than or equal to
                                if (!$field) {
                                    throw new Exception("Does not fit condition");
                                }

                                if (is_array($field) || is_array($values) || !is_numeric($field) || !is_numeric($values) || $field < $values) {
                                    throw new Exception("Does not fit condition");
                                }

                            // greater than to comparisons
                            } elseif ($instructions['type'] == "greater than") {
                                // if this isn't set, it's not less than
                                if (!$field) {
                                    throw new Exception("Does not fit condition");
                                }

                                if (is_array($field) || is_array($values) || !is_numeric($field) || !is_numeric($values) || $field <= $values) {
                                    throw new Exception("Does not fit condition");
                                }

                            // less than or equal to comparisons
                            } elseif ($instructions['type'] == "less than or equal to") {
                                // if this isn't set, it's not less than or equal to
                                if (!$field) {
                                    throw new Exception("Does not fit condition");
                                }

                                if (is_array($field) || is_array($values) || !is_numeric($field) || !is_numeric($values) || $field > $values) {
                                    throw new Exception("Does not fit condition");
                                }

                            // less than to comparisons
                            } elseif ($instructions['type'] == "less than") {
                                // if this isn't set, it's not less than
                                if (!$field) {
                                    throw new Exception("Does not fit condition");
                                }

                                if (is_array($field) || is_array($values) || !is_numeric($field) || !is_numeric($values) || $field >= $values) {
                                    throw new Exception("Does not fit condition");
                                }

                            // not-equal comparisons
                            } elseif ($instructions['type'] == "not equal") {
                                // if this isn't set, it's not equal, continue
                                if (!$field) {
                                    continue;
                                }

                                if (!is_array($field)) {
                                    if ($field == $values) {
                                        throw new Exception("Does not fit condition");
                                    }
                                } elseif (in_array($values, $field)) {
                                    throw new Exception("Does not fit condition");
                                }

                            // contains comparisons
                            } elseif ($instructions['type'] == "in") {
                                if (!isset($field)) {
                                    throw new Exception("Does not fit condition");
                                }

                                if (is_array($field)) {
                                    $found = false;

                                    foreach ($field as $option) {
                                        if (in_array($option, $values)) {
                                            $found = true;
                                            break;
                                        }
                                    }

                                    if (!$found) {
                                        throw new Exception("Does not fit condition");
                                    }
                                } elseif (!in_array($field, $values)) {
                                    throw new Exception("Does not fit condition");
                                }
                                
                            } elseif ($instructions['type'] == "not in") {
                                if (!isset($field)) {
                                    throw new Exception("Does not fit condition");
                                }

                                if (is_array($field)) {
                                    foreach ($field as $option) {
                                        if (in_array($option, $values)) {
                                            throw new Exception("Does not fit condition");
                                        }
                                    }
                                } elseif (in_array($field, $values)) {
                                    throw new Exception("Does not fit condition");
                                }
                            }

                        // we don't know what this is
                        } else {
                            throw new Exception("Unknown kind of condition");
                        }

                    } catch (Exception $e) {
                        unset($this->members[$username]);
                        continue;
                    }
                }
            }
        }
    }


    /**
     * Sorts the current users by $field and $direction
     *
     * @param string  $field  Field to sort on
     * @param string  $direction  Direction to sort
     * @return void
     */
    public function sort($field="username", $direction="asc")
    {
        // no content, abort
        if (!count($this->members)) {
            return;
        }

        // sort by random, short-circuit
        if ($field == "random") {
            shuffle($this->members);
            return;
        }

        // sort by field
        usort($this->members, function($item_1, $item_2) use ($field) {
            // grab values
            $value_1 = array_get($item_1, $field, null);
            $value_2 = array_get($item_2, $field, null);

            // compare the two values
            // ----------------------------------------------------------------
            return Helper::compareValues($value_1, $value_2);
        });
        
        // do we need to flip the order?
        if ($direction == "desc") {
            $this->members = array_reverse($this->members);
        }
    }


    /**
     * Limits the number of items kept in the set
     *
     * @param int  $limit  The maximum number of items to keep
     * @param int  $offset  Offset the starting point of the chop
     * @return void
     */
    public function limit($limit=null, $offset=0)
    {
        if (is_null($limit) && $offset === 0) {
            return;
        }

        $this->members = array_slice($this->members, $offset, $limit, true);
    }


    /**
     * Grabs one page from a paginated set
     *
     * @param int  $page_size  Size of page to grab
     * @param int  $page  Page number to grab
     * @return void
     */
    public function isolatePage($page_size, $page)
    {
        $count = $this->count();

        // return the last page of results if $page is out of range
        if (Config::getFixOutOfRangePagination()) {
            if ($page_size * $page > $count) {
                $page = ceil($count / $page_size);
            } elseif ($page < 1) {
                $page = 1;
            }
        }

        $offset = ($page - 1) * $page_size;
        $this->limit($page_size, $offset);
    }
    
    
    
    // get data ready
    // ------------------------------------------------------------------------

    /**
     * Prepares the member data for use in loops
     *
     * @param bool  $parse_biography  Parse content? This is a performance hit.
     * @param bool  $override_flag  Override `prepared` flag and re-loop?
     * @return void
     */
    public function prepare($parse_biography=true, $override_flag=false)
    {
        if ($this->prepared && !$override_flag) {
            return;
        }

        $this->prepared = true;
        $count = $this->count();
        $i = 1;

        // loop through the content adding contextual data
        foreach ($this->members as $member => $item) {
            $username = $item['username'];

            $this->members[$member]['first']         = ($i === 1);
            $this->members[$member]['last']          = ($i === $count);
            $this->members[$member]['count']         = $i;
            $this->members[$member]['total_results'] = $count;
            
            $file = sprintf(Config::getConfigPath() . '/users/%s.yaml', $username);

            // parse full content if that's been requested
            if ($parse_biography && $file) {
                $raw_file = substr(File::get($file), 3);
                $divide = strpos($raw_file, "\n---");

                $this->members[$member]['biography_raw']  = trim(substr($raw_file, $divide + 4));
                $this->members[$member]['biography']      = Content::parse($this->members[$member]['biography_raw'], $item);
            }

            $i++;
        }
    }


    /**
     * Supplements the members in the set
     *
     * @param array  $context  Context for supplementing
     * @return void
     */
    public function supplement($context=array())
    {
        if ($this->supplemented) {
            return;
        }

        $this->supplemented = true;
        $context = Helper::ensureArray($context);

        // determine context
        $given_context = $context;
        $context = array(
            'total_found' => (isset($given_context['total_found'])) ? $given_context['total_found'] : null,
        );

        // loop through content, supplementing each record with data
        foreach ($this->members as $data) {
            // total entries
            if ($context['total_found']) {
                $data['total_found']  = (int) $context['total_found'];
            }
        }
    }



    // custom adjustments
    // ------------------------------------------------------------------------

    /**
     * Custom-supplement members
     *
     * @param string  $key  Key to supplement
     * @param callable  $callback  Callback that returns the value to set
     * @return void
     */
    public function customSupplement($key, $callback)
    {
        foreach ($this->members as $username => $member_data) {
            $this->members[$username][$key] = call_user_func($callback, $username);
        }
    }


    /**
     * Custom-filter members
     *
     * @param string  $callback  Callback that, when returning false, removes members from list
     * @return void
     */
    public function customFilter($callback)
    {
        foreach ($this->members as $username => $member_data) {
            if (!call_user_func($callback, $this->members[$username])) {
                unset($this->members[$username]);
            }
        }
    }


    // retrieve data
    // ------------------------------------------------------------------------
    
    /**
     * Get the member data stored within
     *
     * @param bool  $parse_biography  Parse biography?
     * @param bool  $supplement  Supplement content?
     * @return array
     */
    public function get($parse_biography=true, $supplement=true)
    {
        if ($supplement) {
            $this->supplement();
        }
        $this->prepare($parse_biography);
        return $this->members;
    }


    /**
     * Extracts data without altering it
     *
     * @return array
     */
    public function extract()
    {
        return $this->members;
    }
}