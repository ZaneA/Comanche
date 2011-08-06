<?php
// Apache log parser

namespace Comanche;

class Parser {
    private $records, $paths, $filters, $matcher;

    public function __construct($matcher) {
        $this->records = array();
        $this->paths = array();
        $this->filters = array();
        $this->matcher = $matcher;

        $this->addFilter(function ($data) {
            $data->timestamp = strtotime($data->time);
        });
    }

    public function addPath($path) {
        if (is_readable($path)) {
            $this->paths[] = $path;
        } else {
            throw new \Exception("{$path} given to addPath is not a readable file");
        }
    }

    public function addFilter($func) {
        if (is_callable($func)) {
            $this->filters[] = $func;
        } else {
            throw new \Exception('Variable given to addFilter is not callable');
        }
    }

    public function parseLine($line) {
        if (preg_match($this->matcher, $line, $matches)) {
            $matches = (object)$matches;
            foreach ($this->filters as $filter) {
                if ($filter($matches) === false)
                    return null;
            }
            return $matches;
        }

        return null;
    }

    private function parse() {
        foreach ($this->paths as $path) {
            $lines = file($path);
            foreach ($lines as $line) {
                if ($pLine = $this->parseLine($line)) {
                    $this->records[] = $pLine;
                }
            }
        }
    }

    public function sort($func) {
        if (empty($this->records)) $this->parse();

        if (is_callable($func)) {
            usort($this->records, $func);
        } else {
            throw new \Exception('Variable given to sort is not callable');
        }
    }

    public function each($func) {
        if (empty($this->records)) $this->parse();

        if (is_callable($func)) {
            foreach ($this->records as $record) {
                $func($record);
            }
        } else {
            throw new \Exception('Variable given to each is not callable');
        }
    }

    public function filterBetween($fromTime, $toTime) {
        $fromTime = strtotime($fromTime);
        $toTime = strtotime($toTime);

        if (!$fromTime OR !$fromTime) {
            throw new \Exception('From and till times could not be parsed');
        }

        return function ($data) use ($fromTime, $toTime) {
            $pTime = strtotime($data->time);

            if (!($pTime > $fromTime AND $pTime < $toTime))
                return false;
        };
    }

    public function sortByTime() {
        return function ($a, $b) {
            if ($a->timestamp == $b->timestamp) return 0;

            return ($a->timestamp < $b->timestamp) ? 1 : -1;
        };
    }
}