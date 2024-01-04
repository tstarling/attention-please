<?php

const MIN_INTERVAL = 2;
const MIN_CHALLENGES = 5;
const MAX_CHALLENGES = 5;
const CHALLENGE_DURATION = 20;
const HINT_POSITION = 3;
const CHALLENGE_START = 8;
const SCORE_EARLY = -1;
const SCORE_TIMEOUT = 2;

$n = 1_000_000;
$histogram = array_fill( 0, 10, 0 );
$total = 0;

for ( $i = 0; $i < $n; $i++ ) {
	$challenge = generate();
	//$guess = generate();
	$guess = generateCentralGuesses();
	$score = score( $challenge, $guess );
	$total += $score;
	$bucket = (int)( $score / 10 );
	if ( $bucket > 9 ) {
		$bucket = 9;
	}
	$histogram[$bucket]++;
}

print "Average score: " . ( $total / $n ) . "\n";
for ( $i = 0; $i < 10; $i++ ) {
	$startBucket = $i * 10;
	$endBucket = $startBucket + 9;
	$percentage = $histogram[$i] / $n * 100;
	printf( "%2d - %2d: %5.1f %%\n", $startBucket, $endBucket, $percentage );
}

$cumlPercentage = 0;
for ( $i = 0; $i < 9; $i++ ) {
	$endBucket = ( $i + 1 ) * 10;
	$percentage = $histogram[$i] / $n * 100;
	$cumlPercentage += $percentage;
	printf( ">%3d: %5.1f %%\n", $endBucket, 100 - $cumlPercentage );
}


function generate() {
	$times = [];
	if ( MIN_CHALLENGES === MAX_CHALLENGES ) {
		$n = MIN_CHALLENGES;
	} else {
		$n = mt_rand( MIN_CHALLENGES, MAX_CHALLENGES );
	}
	for ( $i = 0; $i < $n; $i++ ) {
		$r = round(
			mt_rand() / mt_getrandmax()
			* ( CHALLENGE_DURATION - $i * MIN_INTERVAL * 2 ) + CHALLENGE_START,
		3 );
		foreach ( $times as $t ) {
			$keepoutStart = $t - MIN_INTERVAL;
			if ( $r > $keepoutStart ) {
				$r += MIN_INTERVAL * 2;
			}
		}
		$times[] = $r;
		sort( $times );
	}
	$times[] = HINT_POSITION;
	sort( $times );
	return $times;
}

function generateCentralGuesses() {
	$times = [ HINT_POSITION ];
	$n = round( ( MIN_CHALLENGES + MAX_CHALLENGES ) / 2 );
	$t = CHALLENGE_START + CHALLENGE_DURATION / $n;
	for ( $i = 0; $i < $n; $i++ ) {
		$times[] = $t;
		$t += CHALLENGE_DURATION / $n;
	}
	return $times;
}

function debug( $msg ) {
	//print "$msg\n";
}

function score( $answers, $clickTimes ) {
	$answerIndex = 0;
	$numAnswers = count( $answers );
	$score = 0;
	debug( "Answers: " . implode( ", ", $answers ) );
	foreach ( $clickTimes as $t ) {
		if ( $answerIndex >= $numAnswers ) {
			debug( "Click at time $t: past the end" );
			$score += SCORE_EARLY;
			continue;
		}
		while ( $t > $answers[$answerIndex] + SCORE_TIMEOUT ) {
			$answerIndex ++;
			if ( $answerIndex === $numAnswers ) {
				debug( "Click at time $t: reached the end" );
				break 2;
			}
		}
		if ( $t < $answers[$answerIndex] ) {
			debug( "Click at time $t: early" );
			$score += SCORE_EARLY;
			continue;
		}
		$clickScore = ( $answers[$answerIndex] + SCORE_TIMEOUT - $t ) / SCORE_TIMEOUT;
		debug( "Click at time $t: (#$answerIndex) $clickScore" );
		$score += $clickScore;
		// Consume answer
		$answerIndex ++;
	}
	if ( $score < 0 ) {
		$score = 0;
	}
	return $score / $numAnswers * 100;
}
