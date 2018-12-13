<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id$
 */

$fortunes[] = "If anything can go wrong ....it will.";
$fortunes[] = "Anything dropped while working on a car will roll underneath to the exact center.";
$fortunes[] = "The chances of a piece of bread falling butter side down is directly proportional to the cost of the carpet.";
$fortunes[] = "The light at the end of the tunnel is the headlamp of an oncoming train.";
$fortunes[] = "A $200.00 picture tube will protect a 10 cent fuse by blowing first.";
$fortunes[] = "As events transpire as a function of time, tend to move towards a higher state of entropy.";
$fortunes[] = "The worst or stupidest ideas are always the most popular.";
$fortunes[] = "In front of every silver lining, is a cloud.";
$fortunes[] = "Save yourself a lot of worry, don't burn your bridges until you come to them.";
$fortunes[] = "Simple jobs will always be put off, because there will be time to do them later.";
$fortunes[] = "Never make a decision you can get someone else to make.";
$fortunes[] = "The person who pays the least, complains the most.";
$fortunes[] = "There is no time like the present for postponing what you want to do.";
$fortunes[] = "The more we complicate the plan, the greater the chance of failure.";
$fortunes[] = "Whatever hits the fan will not be evenly distributed.";
$fortunes[] = "A meeting is an event at which the minutes are kept and hours are lost.";
$fortunes[] = "Never leave the room during a committee formation or you're elected.";
$fortunes[] = "For every vision there is an equal and opposite revision.";
$fortunes[] = "If you hit two keys on a keyboard, the one you don't want shows up.";
$fortunes[] = "The cream rises to the top, so does the scum.";
$fortunes[] = "Any task worth doing was worth doing yesterday.";
$fortunes[] = "Teamwork is essential. It allows you to blame someone else.";
$fortunes[] = "Important letters which contain no errors will develop errors in the mail.";
$fortunes[] = "Science is true. Don't be misled by fact.";
$fortunes[] = "Rule for precision: Measure with a micrometer. Mark with chalk.. Cut with an axe.";
$fortunes[] = "Nothing is ever so bad, that it can't get worse.";
$fortunes[] = "If a series of events can go wrong, it will do so in the worst possible sequence.";
$fortunes[] = "After things have gone from bad to worse, the cycle will repeat itself. When the going gets tough, everyone leaves.";
$fortunes[] = "If you wait it will go away. If it was bad, it'll come back.";
$fortunes[] = "Everything depends. Nothing is always. Everything is something. No matter what goes wrong, there is always someone who knew it would.";
$fortunes[] = "Complex problems have simple, easy to understand wrong answers. To err is human, but to really foul things up, requires a computer.";
$fortunes[] = "A computer program does what you tell it to do, not what you want it to do.";
$fortunes[] = "When putting it into memory, remember where you put it.";
$fortunes[] = "Never test an error condition you don't know how to handle.";
$fortunes[] = "Opportunity always knocks at the least opportune times.";
$fortunes[] = "In order for something to come clean, something else must get dirty. Everyone lies, but it doesn't matter, since nobody listens.";
$fortunes[] = "Nothing is ever done for the right reason.";
$fortunes[] = "If everybody doesn't want it, nobody gets it.";
$fortunes[] = "The secret to success is sincerity, once you can fake it, you've got it made.";
$fortunes[] = "To pick the expert, pick the one who predicts the job will take the longest and cost the most.";
$fortunes[] = "An expert is anyone from out of town.";
$fortunes[] = "Indecision is the basis for flexibility.";
$fortunes[] = "Never create a problem for which you don't have the answer.";
$fortunes[] = "A fool and his money are soon partners.";
$fortunes[] = "The first place to look for anything is the last place you would expect to find it.";
$fortunes[] = "Things equal to nothing else are equal to each other.";
$fortunes[] = "You always find something the last place you look.";
$fortunes[] = "An optimist believes we live in the best of all possible worlds. A pessimist fears this is true.";
$fortunes[] = "The time it takes to rectify a situation is inversely proportional to the time it took to do the damage.";
$fortunes[] = "The item you had your eye on the minute you walk in will be taken by the person in front of you.";
$fortunes[] = "The longer you stand in line, the greater the likelihood that you are standing in the wrong line.";
$fortunes[] = "A crisis is when you can't say \"Lets forget the whole thing\".";
$fortunes[] = "It is impossible for an optimist to be pleasantly surprised.";
$fortunes[] = "The slowest checker is always at the quick check out lane.";
$fortunes[] = "Washing your car to make it rain doesn't work.";
$fortunes[] = "You can always find what you're not looking for.";
$fortunes[] = "Never draw what you can copy. Never copy what you can trace. Never trace what you can cut and paste.";
$fortunes[] = "Whenever you cut your fingernails, You will need them an hour later.";
$fortunes[] = "Forgive and remember.";
$fortunes[] = "Photographer: The best shots happen right after the last frame is exposed.";
$fortunes[] = "Photographer: The best shots are attempted through the lens cap.";
$fortunes[] = "In an organization there is always one person who knows what is going on. This person must get fired.";
$fortunes[] = "Anyone can make a decision given enough facts. A good manager can make a decision without enough facts. A perfect manager can operate in perfect ignorance.";
$fortunes[] = "Don't let your superiors know you are better than they are.";
$fortunes[] = "If you don't care where you are, you ain't lost.";
$fortunes[] = "Some errors will always go unnoticed until the program is saved.";
$fortunes[] = "The best way to inspire fresh thoughts is to seal the letter.";
$fortunes[] = "For every action there is an equal and opposite criticism.";
$fortunes[] = "The bigger they are, the harder they hit.";
$fortunes[] = "When somebody drops something, everyone will kick it around instead of picking it up.";

mt_srand ((double) microtime()* 100000000);
$layout['fortune'] = $fortunes[mt_rand(0,count($fortunes)-1)];

?>
