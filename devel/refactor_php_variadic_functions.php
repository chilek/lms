<?php
/**
 * Skrypt refaktoryzujący funkcje variadic w PHP.
 * Obsługuje konsolidację:
 * 1. Chained AND: isset($a) && isset($b) => isset($a, $b)
 * 2. Standalone consecutive: var_dump($a); var_dump($b); => var_dump($a, $b);
 * 3. Target consecutive: array_push($x, $a); array_push($x, $b); => array_push($x, $a, $b);
 * 4. Reassignment: $x = array_merge($x, $a); $x = array_merge($x, $b); => $x = array_merge($x, $a, $b);
 *
 * Usage:
 * php refactor_php_variadic_functions.php <file|directory> [--dry-run]
 */

if ($argc < 2) {
    die("Usage: php refactor_php_variadic_functions.php <file|directory> [--dry-run]\n");
}

$path = $argv[1];
$dryRun = in_array('--dry-run', $argv);
$ignoredDirs = ['vendor', '.git', 'node_modules'];

// ----------------------- Zbieranie plików -----------------------
$files = [];

if (is_file($path) && substr($path, -4) === '.php') {
    $files[] = $path;
} else {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        $filePath = $file->getPathname();
        foreach ($ignoredDirs as $dir) {
            if (strpos($filePath, DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR) !== false) {
                continue 2;
            }
        }
        if (substr($filePath, -4) === '.php') {
            $files[] = $filePath;
        }
    }
}

// ----------------------- Klasyfikacja Funkcji -----------------------
$funcsChained = ['isset']; // &&
$funcsStandalone = ['var_dump', 'compact'];
$funcsTarget = ['array_push', 'array_unshift', 'array_multisort']; // same first arg
$funcsReassign = ['array_merge', 'array_merge_recursive', 'array_intersect', 'array_intersect_assoc',
                  'array_intersect_key', 'array_diff', 'array_diff_assoc', 'array_diff_key', 'max', 'min']; // $x = func($x, ...)

// ----------------------- Helpery Tokenów -----------------------
function getTokenValue($t)
{
    return is_string($t) ? $t : $t[1];
}

function skipWhitespaceAndComments($tokens, &$idx, $count)
{
    while ($idx < $count && is_array($tokens[$idx]) && in_array($tokens[$idx][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])) {
        $idx++;
    }
}

function parseFunctionCall($tokens, $startIdx, $count)
{
    $idx = $startIdx;
    $idx++; // skip function name token
    skipWhitespaceAndComments($tokens, $idx, $count);
    if ($idx >= $count || getTokenValue($tokens[$idx]) !== '(') {
        return null;
    }
    $idx++; // skip '('
    
    $args = [];
    $currentArg = [];
    $parenDepth = 1;
    
    while ($idx < $count) {
        $t = $tokens[$idx];
        $tval = getTokenValue($t);
        
        if ($tval === '(') {
            $parenDepth++;
        }
        if ($tval === ')') {
            $parenDepth--;
            if ($parenDepth === 0) {
                if (!empty($currentArg)) {
                    $args[] = $currentArg;
                }
                $idx++; // consume ')'
                return ['args' => $args, 'endIdx' => $idx];
            }
        }
        
        if ($parenDepth === 1 && $tval === ',') {
            $args[] = $currentArg;
            $currentArg = [];
        } else {
            // strip leading space of args if they are purely leading space
            if (empty($currentArg) && is_array($t) && $t[0] == T_WHITESPACE && strpos($tval, "\n") === false) {
                // skip leading inline whitespace
            } else {
                $currentArg[] = $t;
            }
        }
        $idx++;
    }
    return null;
}

function compareTokens($tokensA, $tokensB)
{
    if (count($tokensA) !== count($tokensB)) {
        return false;
    }
    foreach ($tokensA as $i => $ta) {
        if (getTokenValue($ta) !== getTokenValue($tokensB[$i])) {
            return false;
        }
    }
    return true;
}

// ----------------------- Przetwarzanie -----------------------
foreach ($files as $filePath) {
    $source = file_get_contents($filePath);
    $tokens = token_get_all($source);
    $count = count($tokens);
    $newSource = '';
    $i = 0;
    $modified = false;

    while ($i < $count) {
        $t = $tokens[$i];
        $tval = getTokenValue($t);
        
        // 1. Chained ISSET (i inne łączone logicznie)
        if (is_array($t) && ($t[0] == T_ISSET || (in_array(strtolower($tval), $funcsChained) && $t[0] == T_STRING))) {
            $funcName = is_array($t) && $t[0] == T_ISSET ? 'isset' : $tval;
            $call = parseFunctionCall($tokens, $i, $count);
            
            if ($call !== null) {
                $consolidatedArgs = $call['args'];
                $idx = $call['endIdx'];
                $hasChain = false;
                
                while ($idx < $count) {
                    $tempIdx = $idx;
                    skipWhitespaceAndComments($tokens, $tempIdx, $count);
                    
                    if ($tempIdx < $count && is_array($tokens[$tempIdx]) && in_array($tokens[$tempIdx][0], [T_BOOLEAN_AND, T_LOGICAL_AND])) {
                        $tempIdx++; // skip &&
                        skipWhitespaceAndComments($tokens, $tempIdx, $count);
                        
                        $nextT = $tokens[$tempIdx];
                        $nextTval = getTokenValue($nextT);
                        $isNextMatch = false;
                        if ($funcName === 'isset' && is_array($nextT) && $nextT[0] == T_ISSET) {
                            $isNextMatch = true;
                        }
                        if ($funcName !== 'isset' && is_array($nextT) && $nextT[0] == T_STRING && strtolower($nextTval) === strtolower($funcName)) {
                            $isNextMatch = true;
                        }
                        
                        if ($isNextMatch) {
                            $nextCall = parseFunctionCall($tokens, $tempIdx, $count);
                            if ($nextCall !== null) {
                                foreach ($nextCall['args'] as $a) {
                                    $consolidatedArgs[] = $a;
                                }
                                $idx = $nextCall['endIdx'];
                                $hasChain = true;
                                continue;
                            }
                        }
                    }
                    break;
                }
                
                if ($hasChain) {
                    $modified = true;
                    $newSource .= $funcName . '(';
                    foreach ($consolidatedArgs as $k => $arg) {
                        if ($k > 0) {
                            $newSource .= ', ';
                        }
                        foreach ($arg as $at) {
                            $newSource .= getTokenValue($at);
                        }
                    }
                    $newSource .= ')';
                    $i = $idx;
                    continue;
                }
            }
        }
        
        // Zapisz normalny token
        $newSource .= $tval;
        $i++;
    }

    // Drugi przebieg - sekwencje instrukcji (Sequential Statements)
    if ($modified) {
        $tokens = token_get_all($newSource);
        $count = count($tokens);
    }
    
    $finalSource = '';
    $i = 0;
    $seqModified = false;
    
    while ($i < $count) {
        // Look for statements like: var_dump(...);  or  array_push($a, ...); or $a = array_merge($a, ...);
        $t = $tokens[$i];
        
        // Match assignment: $var = func(...) ;
        $assignVar = null;
        $tempIdx = $i;
        if (is_array($t) && $t[0] == T_VARIABLE) {
            $assignVarStr = getTokenValue($t);
            $assignVarTokens = [$t];
            $tempIdx++;
            skipWhitespaceAndComments($tokens, $tempIdx, $count);
            // could have array dim $a['b'] = func
            while ($tempIdx < $count && (getTokenValue($tokens[$tempIdx]) === '[' || is_array($tokens[$tempIdx]))) {
                if (getTokenValue($tokens[$tempIdx]) === '=') {
                    break;
                }
                 $assignVarTokens[] = $tokens[$tempIdx];
                 $tempIdx++;
            }
            if ($tempIdx < $count && getTokenValue($tokens[$tempIdx]) === '=') {
                $tempIdx++;
                skipWhitespaceAndComments($tokens, $tempIdx, $count);
                if ($tempIdx < $count && is_array($tokens[$tempIdx]) && $tokens[$tempIdx][0] == T_STRING) {
                    $assignVar = $assignVarTokens;
                }
            }
        }
        
        $funcStartIdx = $assignVar ? $tempIdx : $i;
        $funcT = $tokens[$funcStartIdx];
        
        if (is_array($funcT) && $funcT[0] == T_STRING) {
            $funcName = strtolower(getTokenValue($funcT));
            
            $isStandalone = in_array($funcName, $funcsStandalone) && !$assignVar;
            $isTarget     = in_array($funcName, $funcsTarget) && !$assignVar;
            $isReassign   = in_array($funcName, $funcsReassign) && $assignVar;
            
            if ($isStandalone || $isTarget || $isReassign) {
                $call = parseFunctionCall($tokens, $funcStartIdx, $count);
                
                if ($call !== null) {
                    $endIdx = $call['endIdx'];
                    skipWhitespaceAndComments($tokens, $endIdx, $count);
                    
                    if ($endIdx < $count && getTokenValue($tokens[$endIdx]) === ';') {
                        $endIdx++; // skip ';'
                        
                        $isValidFirstCall = true;
                        $targetArg = null;
                        
                        if ($isTarget || $isReassign) {
                            if (count($call['args']) < 1) {
                                $isValidFirstCall = false;
                            } else {
                                $targetArg = $call['args'][0];
                            }
                            
                            if ($isReassign && $isValidFirstCall) {
                                if (!compareTokens($assignVar, $targetArg)) {
                                    $isValidFirstCall = false;
                                }
                            }
                        }
                        
                        if ($isValidFirstCall) {
                            $consolidatedArgs = $call['args'];
                            $hasSequence = false;
                            $idx = $endIdx;
                            
                            while ($idx < $count) {
                                $scanIdx = $idx;
                                skipWhitespaceAndComments($tokens, $scanIdx, $count);
                                
                                // check assignment match
                                $nextAssignVar = null;
                                if ($isReassign) {
                                    $nextAssignTokens = [];
                                    while ($scanIdx < $count && getTokenValue($tokens[$scanIdx]) !== '=') {
                                        if (!in_array(is_array($tokens[$scanIdx]) ? $tokens[$scanIdx][0] : '', [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])) {
                                            $nextAssignTokens[] = $tokens[$scanIdx];
                                        }
                                        $scanIdx++;
                                    }
                                    if ($scanIdx < $count && getTokenValue($tokens[$scanIdx]) === '=') {
                                        $nextAssignVar = $nextAssignTokens;
                                        $scanIdx++;
                                        skipWhitespaceAndComments($tokens, $scanIdx, $count);
                                    }
                                }
                                
                                if ($scanIdx < $count && is_array($tokens[$scanIdx]) && $tokens[$scanIdx][0] == T_STRING && strtolower(getTokenValue($tokens[$scanIdx])) === $funcName) {
                                    $nextCall = parseFunctionCall($tokens, $scanIdx, $count);
                                    
                                    if ($nextCall !== null) {
                                        $nextEndIdx = $nextCall['endIdx'];
                                        skipWhitespaceAndComments($tokens, $nextEndIdx, $count);
                                        
                                        if ($nextEndIdx < $count && getTokenValue($tokens[$nextEndIdx]) === ';') {
                                            $nextEndIdx++; // skip ';'
                                            
                                            $isValidNext = true;
                                            if ($isTarget || $isReassign) {
                                                if (count($nextCall['args']) < 1) {
                                                    $isValidNext = false;
                                                } else if (!compareTokens($targetArg, $nextCall['args'][0])) {
                                                    $isValidNext = false;
                                                }
                                            }
                                            if ($isReassign && $isValidNext) {
                                                if (!compareTokens($assignVar, $nextAssignVar)) {
                                                    $isValidNext = false;
                                                }
                                            }
                                            
                                            if ($isValidNext) {
                                                $argsToAdd = $nextCall['args'];
                                                if ($isTarget || $isReassign) {
                                                    array_shift($argsToAdd); // drop the target arg
                                                }
                                                foreach ($argsToAdd as $a) {
                                                    $consolidatedArgs[] = $a;
                                                }
                                                
                                                $hasSequence = true;
                                                $idx = $nextEndIdx;
                                                continue;
                                            }
                                        }
                                    }
                                }
                                break;
                            }
                            
                            if ($hasSequence) {
                                $seqModified = true;
                                if ($assignVar) {
                                    foreach ($assignVar as $at) {
                                        $finalSource .= getTokenValue($at);
                                    }
                                    $finalSource .= ' = ';
                                }
                                $finalSource .= $tokenIdToName = getTokenValue($funcT) . '(';
                                foreach ($consolidatedArgs as $k => $arg) {
                                    if ($k > 0) {
                                        $finalSource .= ', ';
                                    }
                                    foreach ($arg as $at) {
                                        $finalSource .= getTokenValue($at);
                                    }
                                }
                                $finalSource .= ');';
                                $i = $idx;
                                continue;
                            }
                        }
                    }
                }
            }
        }
        
        $finalSource .= getTokenValue($t);
        $i++;
    }

    if ($modified || $seqModified) {
        $outSource = $seqModified ? $finalSource : $newSource;
        if ($dryRun) {
            echo "Would change: $filePath\n";
        } else {
            file_put_contents($filePath, $outSource);
            echo "Changed: $filePath\n";
        }
    }
}
