<?php

namespace App;

use SimpleXMLElement;
use Carbon\Carbon;

class Transformer
{
    public function parse(string $xml) : array
    {
        $xml = new SimpleXMLElement($xml);
        $xml->registerXPathNamespace('dm', 'http://www.digitalmeasures.com/schema/data');

        $query = "//dm:INTELLCONT[dm:RESEARCH_SCOPE='Research-related'][dm:STATUS='Published' or dm:STATUS='Accepted'][dm:INTELLCONT_AUTH[dm:FACULTY_NAME=../../@userId][dm:DISPLAY='Yes']]";

        foreach ($xml->xpath($query) as $publication) {
            // removes duplicates
            $id = (string)$publication['id'];
            if (isset($publications[$id])) {
                continue;
            }

            try {
                $publication->sortDate = $this->getDate($publication);
            } catch (MissingDate $e) {
                continue;
            }

            $publications[$id] = $publication;
        }

        $this->sort($publications);

        return $publications;
    }

    /**
     * orders newest publications first
     */
    protected function sort(array &$publications)
    {
        usort($publications, function ($a, $b) {
            $first = new Carbon($a->sortDate);
            $second = new Carbon($b->sortDate);

            if ($first->gt($second)) {
                return -1;
            }

            if ($first->lt($second)) {
                return 1;
            }

            return 0;
        });
    }

    protected function getDate(SimpleXMLElement $publication) : string
    {
        foreach (['PUB_END', 'ACC_END', 'SUB_END'] as $date) {
            $date = (string)$publication->$date;

            if ($date) {
                return $date;
            }
        }

        throw new MissingDate;
    }
}

class MissingDate extends \InvalidArgumentException {}
