<?php

namespace Iris\Config\CRM\sections\Poll_Question;

use Config;

class dg_Poll_Question extends Config
{
    function __construct()
    {
        parent::__construct([
            'common/Lib/lib.php',
        ]);
    }

    function Renumber($params)
    {
        $p_poll_id = $params['_p_id'];
        $p_orderpos = $params['_p_orderpos'];
        $con = $this->connection;

        $select_sql = <<<EOD
update iris_Poll_Question set orderpos = (orderpos::integer - 1)::varchar
where PollID = :pollid 
and orderpos::integer > :pos
EOD;
        $statement = $con->prepare($select_sql);
        $statement->execute(array(
            ':pollid' => $p_poll_id,
            ':pos' => $p_orderpos,
        ));
    }
}
