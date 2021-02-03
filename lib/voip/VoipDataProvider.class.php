<?php

/*!
 * \class VoipDataProvider
 * \brief Abstract class for voip data providers.
 * If you want use custom data providers extend this class.
 *
 * example use:
 * class yourCustom extends voipDataProvider {
 *     ...
 *     ...
 * }
 *
 * $estimate = new Estimate( yourCustom::getInstance() );
 */
abstract class VoipDataProvider
{

    // extortion use singleton pattern for providers
    final protected function __construct()
    {
    }
    public static function getInstance()
    {
    }


    abstract protected function getGroupByPrefix($prefix, $tariffid);

    abstract protected function getCustomerByPhone($number);

    abstract protected function getLongestPrefix($phone_number, $tariffid);

    abstract protected function getRules($groupid, $tariffid);

    abstract protected function getCustomerRuleStates($voipaccountid, $groupid);

    /*!
     * \brief Return best mached rule from array passed by parameter.
     *
     * \param  array  $rules array with rules
     * \return array         array with matched rule
     * \return NULL          when array is empty
     */
    public function getBestRule(array $rules)
    {
        if (!$rules) {
            return null;
        }

        $k            = array_keys($rules);
        $best_rule_id = $rules[$k[0]]['ruleid'];
        $price        = $rules[$k[0]]['price'];

        foreach ($rules as $sr) {
            if ($price > $sr['price']) {
                $best_rule_id = $sr['ruleid'];
                $price        = $sr['price'];
            }
        }

        return $rules[$best_rule_id];
    }
}
