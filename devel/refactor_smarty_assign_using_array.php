<?php

if ($argc < 2) {
    die("Usage: php refactor_smarty_assign_using_array.php <file|directory>\n");
}

$target = $argv[1];

function getTokens($source)
{
    return token_get_all($source);
}

function processFile($filePath)
{
    echo "Processing: $filePath\n";
    $source = file_get_contents($filePath);
    $tokens = token_get_all($source);

    $newSource = '';
    $count = count($tokens);
    $i = 0;

    $modified = false;

    while ($i < $count) {
        // Look for $SMARTY->assign(
        // Expected sequence: T_VARIABLE ($SMARTY) -> T_OBJECT_OPERATOR (->) -> T_STRING (assign) -> (

        $matchFound = false;

        // Check if we are at start of a potential assign block
        if (is_array($tokens[$i]) && $tokens[$i][0] == T_VARIABLE && $tokens[$i][1] == '$SMARTY') {
            // Check ahead for -> assign (
            $j = $i + 1;
            while ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] == T_WHITESPACE) {
                $j++; // skip whitespace
            }

            if ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] == T_OBJECT_OPERATOR) {
                $j++;
                while ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] == T_WHITESPACE) {
                    $j++; // skip whitespace
                }

                if ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] == T_STRING && $tokens[$j][1] == 'assign') {
                    $j++;
                    while ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] == T_WHITESPACE) {
                        $j++; // skip whitespace
                    }

                    if ($j < $count && $tokens[$j] == '(') {
                        // Found valid $SMARTY->assign( start

                        // Now we need to collect this assignment and any immediately following ones
                        $assignments = [];
                        $currentStart = $i;

                        // Parsing loop to collect consecutive assignments
                        while (true) {
                            // Check if this is a $SMARTY->assign call
                            // Rewind slightly logic wise: we are essentially attempting to parse one full statement here
                            // We need to verify it is exactly $SMARTY->assign('key', $val);

                            // Re-verify the sequence for the current potential assignment (since we loop)
                            $tempJ = $currentStart;

                            // Skip whitespace
                            while ($tempJ < $count && is_array($tokens[$tempJ]) && $tokens[$tempJ][0] == T_WHITESPACE) {
                                $tempJ++;
                            }

                            // Must be $SMARTY
                            if (!($tempJ < $count && is_array($tokens[$tempJ]) && $tokens[$tempJ][0] == T_VARIABLE && $tokens[$tempJ][1] == '$SMARTY')) {
                                break;
                            }
                            $tempJ++;

                            while ($tempJ < $count && is_array($tokens[$tempJ]) && $tokens[$tempJ][0] == T_WHITESPACE) {
                                $tempJ++;
                            }
                            if (!($tempJ < $count && is_array($tokens[$tempJ]) && $tokens[$tempJ][0] == T_OBJECT_OPERATOR)) {
                                break;
                            }
                            $tempJ++;

                            while ($tempJ < $count && is_array($tokens[$tempJ]) && $tokens[$tempJ][0] == T_WHITESPACE) {
                                $tempJ++;
                            }
                            if (!($tempJ < $count && is_array($tokens[$tempJ]) && $tokens[$tempJ][0] == T_STRING && $tokens[$tempJ][1] == 'assign')) {
                                break;
                            }
                            $tempJ++;

                            while ($tempJ < $count && is_array($tokens[$tempJ]) && $tokens[$tempJ][0] == T_WHITESPACE) {
                                $tempJ++;
                            }
                            if (!($tempJ < $count && $tokens[$tempJ] == '(')) {
                                break;
                            }
                            $openParenPos = $tempJ;
                            $tempJ++;

                            // Now extraction of arguments: key and value
                            // We only support simple string keys: 'key' or "key"
                            // Value can be complex expression, UP TO the comma

                            while ($tempJ < $count && is_array($tokens[$tempJ]) && $tokens[$tempJ][0] == T_WHITESPACE) {
                                $tempJ++;
                            }

                            $keyTokens = [];
                            if ($tempJ < $count && is_array($tokens[$tempJ]) && $tokens[$tempJ][0] == T_CONSTANT_ENCAPSED_STRING) {
                                $keyTokens[] = $tokens[$tempJ];
                                $tempJ++;
                            } else {
                                // First arg is not a simple string, abort this block logic
                                break;
                            }

                            while ($tempJ < $count && is_array($tokens[$tempJ]) && $tokens[$tempJ][0] == T_WHITESPACE) {
                                $tempJ++;
                            }

                            if (!($tempJ < $count && $tokens[$tempJ] == ',')) {
                                // Only one argument? or "assign by ref"? ignored
                                break;
                            }
                            $tempJ++; // consume comma

                            // Now capturing value until );
                            // Careful with nested parens

                            $valueTokens = [];
                            $parenDepth = 1; // We are inside assign( ...

                            while ($tempJ < $count) {
                                $t = $tokens[$tempJ];
                                if ($t == '(') {
                                    $parenDepth++;
                                } elseif ($t == ')') {
                                    $parenDepth--;
                                }

                                if ($parenDepth == 0) {
                                    // Found closing paren of assign(...)
                                    break;
                                }

                                $valueTokens[] = $t;
                                $tempJ++;
                            }

                            if ($parenDepth != 0) {
                                break; // formatting error or weirdness
                            }

                            $closeParenPos = $tempJ;
                            $tempJ++; // consume )

                            while ($tempJ < $count && is_array($tokens[$tempJ]) && $tokens[$tempJ][0] == T_WHITESPACE) {
                                $tempJ++;
                            }

                            if (!($tempJ < $count && $tokens[$tempJ] == ';')) {
                                // Not a simple statement ending with ;
                                break;
                            }
                            $semiColonPos = $tempJ;
                            $tempJ++; // consume ;

                            // Success finding one assignment
                            $assignments[] = [
                                'key' => $keyTokens,
                                'value' => $valueTokens,
                                'end_pos' => $tempJ // exclusive
                            ];

                            // Prepare to look for next
                            $currentStart = $tempJ;
                        }

                        if (count($assignments) > 1) {
                            // We found consecutive assignments!
                            // Write replacement
                            $matchFound = true;

                            // Check indentation of the first variable to try to preserve it
                            // We can look at whitespace immediately preceding $i if any
                            $baseIndent = "";
                            if ($i > 0 && is_array($tokens[$i - 1]) && $tokens[$i - 1][0] == T_WHITESPACE) {
                                $ws = $tokens[$i - 1][1];
                                $pos = strrpos($ws, "\n");
                                if ($pos !== false) {
                                    $baseIndent = substr($ws, $pos + 1);
                                } else {
                                    $baseIndent = $ws;
                                }
                            }

                            $indentUnit = (strpos($baseIndent, "\t") !== false) ? "\t" : "    ";
                            $twoIndents = $indentUnit . $indentUnit;

                            $newSource .= "\$SMARTY->assign(\n";
                            $newSource .= $baseIndent . $indentUnit . "array(\n";

                            foreach ($assignments as $idx => $assign) {
                                $newSource .= $baseIndent . $twoIndents; // indent
                                // Key
                                foreach ($assign['key'] as $kt) {
                                     $newSource .= is_array($kt) ? $kt[1] : $kt;
                                }
                                $newSource .= " => ";
                                // Value - process token by token
                                $isFirstValueToken = true;
                                $valueOutput = '';
                                foreach ($assign['value'] as $vt) {
                                    $isWhitespace = is_array($vt) && $vt[0] == T_WHITESPACE;
                                    $tokenValue = is_array($vt) ? $vt[1] : $vt;

                                    if ($isFirstValueToken) {
                                        // Strip only leading horizontal whitespace (spaces/tabs), not newlines
                                        $tokenValue = ltrim($tokenValue, " \t");
                                        if ($tokenValue === '') {
                                            continue;
                                        }
                                        $isFirstValueToken = false;
                                    }

                                    // Only modify whitespace tokens, never string content
                                    if ($isWhitespace && strpos($tokenValue, "\n") !== false) {
                                        // Replace each newline + old indent with newline + shifted indent
                                        $wsLines = explode("\n", $tokenValue);
                                        for ($wl = 1; $wl < count($wsLines); $wl++) {
                                            $wsLine = $wsLines[$wl];
                                            if ($indentUnit === "\t") {
                                                $wsLine = str_replace("    ", "\t", $wsLine);
                                            }
                                            $wsLines[$wl] = $twoIndents . $wsLine;
                                        }
                                        $tokenValue = implode("\n", $wsLines);
                                    }

                                    $valueOutput .= $tokenValue;
                                }

                                // Clean trailing whitespace on each line of the final value
                                $valueLines = explode("\n", $valueOutput);
                                foreach ($valueLines as &$vLine) {
                                    $vLine = rtrim($vLine);
                                }
                                $valueOutput = implode("\n", $valueLines);

                                // If value starts with newline, trim trailing space from "=> " on the key line
                                if (strlen($valueOutput) > 0 && $valueOutput[0] === "\n") {
                                    $newSource = rtrim($newSource);
                                }

                                $newSource .= $valueOutput;

                                $newSource .= ",\n";
                            }

                            $newSource .= $baseIndent . $indentUnit . ")\n";
                            $newSource .= $baseIndent . ");";

                            // Advance main loop to end of last assignment
                            $i = $assignments[count($assignments)-1]['end_pos'];
                            $modified = true;
                        } else {
                            // Only 0 or 1 assignment found, not enough to merge, or structure wasn't perfect
                            // Just print the token at $i and move on normally
                            // We fall through to default printer
                        }
                    }
                }
            }
        }

        if (!$matchFound && $i < $count) {
             $token = $tokens[$i];
             $newSource .= is_array($token) ? $token[1] : $token;
             $i++;
        }
    }

    if ($modified) {
        file_put_contents($filePath, $newSource);
        echo "Modified: $filePath\n";
    }
}

if (is_dir($target)) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($target));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            processFile($file->getPathname());
        }
    }
} elseif (is_file($target)) {
    processFile($target);
} else {
    die("Invalid target.\n");
}
