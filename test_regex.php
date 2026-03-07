<?php
$str = "Which element has the chemical symbol 'O'?
a) Gold
b) Silver
c) Oxygen
d) Iron
ANSWER : C

What is the capital city of France?
a) Rome
b) Madrid
c) Berlin
d) Paris
ANSWER : D";

$blocks = preg_split('/(?<=ANSWER\s:\s[A-D])\s*/i', str_replace(' :', ':', $str));
print_r($blocks);

foreach ($blocks as $block) {
    if (empty(trim($block)))
        continue;
    $lines = explode("\n", trim($block));
    $q_text = "";
    $options = ['a' => '', 'b' => '', 'c' => '', 'd' => ''];
    $correct = '';

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line))
            continue;

        if (preg_match('/^[a-d][\)\.]\s*(.*)/i', $line, $matches)) {
            $options[strtolower($line[0])] = $matches[1];
        } elseif (preg_match('/ANSWER\s*:\s*([A-D])/i', $line, $matches)) {
            $correct = strtoupper($matches[1]);
        } else {
            if (empty($options['a']) && empty($options['b']) && empty($options['c']) && empty($options['d']) && empty($correct)) {
                $q_text .= (empty($q_text) ? "" : " ") . $line;
            }
        }
    }
    echo "Q: $q_text\n";
    print_r($options);
    echo "Correct: $correct\n\n";
}
