<?php

namespace AppBundle\Tools;

class Shuffle
{
    public static function shuffleAssoc(array $array)
    {
        $keys = array_keys($array);
        shuffle($keys);
        $new = array_flip($keys);
        array_walk(
            $new,
            function (&$value, $key) use ($array) {
                $value = $array[$key];
            }
        );

        return $new;
    }

    public static function shuffleQuestions(array $array)
    {
        $shuffledQuestions = [];
        $questionSerie = [];
        foreach ($array as $key => $question) {
            if (substr($key, 0, 5) == 'intro') {
                if ($questionSerie) {
                    $shuffledQuestions = array_merge($shuffledQuestions, self::shuffleAssoc($questionSerie));
                }
                $questionSerie = [];
                $shuffledQuestions = array_merge($shuffledQuestions, [$key => $question]);
            } else {
                $questionSerie[$key] = $question;
            }
        }
        if ($questionSerie) {
            $shuffledQuestions = array_merge($shuffledQuestions, self::shuffleAssoc($questionSerie));
        }

        return $shuffledQuestions;
    }
}
