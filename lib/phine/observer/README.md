Observer
========

[![Build Status][]](https://travis-ci.org/phine/lib-observer)
[![Coverage Status][]](https://coveralls.io/r/phine/lib-observer)
[![Latest Stable Version][]](https://packagist.org/packages/phine/observer)
[![Total Downloads][]](https://packagist.org/packages/phine/observer)

A PHP library that implements the observer pattern.

Summary
-------

This library provides an implementation of the [observer pattern][]. You can
use it to create other libraries such as event managers, state machines, MVC
frameworks, and even provide a plugin system for application.

Requirement
-----------

- PHP >= 5.3.3
- [Phine Exception][] >= 1.0.0

Installation
------------

Via [Composer][]:

    $ composer require "phine/observer=~2.0"

Usage
-----

To create a subject, you will need to either create your own implementation
of `SubjectInterface`, or use the bundled `Subject` class.

```php
use Phine\Observer\Subject;

$subject = new Subject();
```

### Observing

You will then need to create your own implementation of `ObserverInterface`
to observe changes made to the subject. You may use multiple instances of
the observer implementation, or even the same instance multiple times.

```php
use Phine\Observer\ObserverInterface;
use Phine\Observer\SubjectInterface;

// register a few instances
$subject->registerObserver(new Message('First'));
$subject->registerObserver(new Message('Second'));

// register the same one twice
$reuse = new Message('Third');

$subject->registerObserver($reuse);
$subject->registerObserver($reuse);

// notify all observers of an update
$subject->notifyObservers();

/**
 * Simply echos a message when updated.
 */
class Message implements ObserverInterface
{
    /**
     * The message to echo.
     *
     * @var string
     */
    private $message;

    /**
     * Sets the message to echo on update.
     *
     * @param string $message The message.
     */
    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * {@inheritDoc}
     */
    public function receiveUpdate(SubjectInterface $subject)
    {
        echo $this->message, "\n";
    }
}
```

With the example above, you can expect the following output:

    First
    Second
    Third
    Third

### Prioritizing Observers

Implementations of `SubjectInterface` support prioritizing observers during
registration (`registerObserver()`). By default, shown in all the examples
provided, the priority is `SubjectInterface::FIRST_PRIORITY` (which is `0`,
zero). You may, however, specify your own priority:

```php
$subject->registerObserver(new Message('A'), SubjectInterface::LAST_PRIORITY);
$subject->registerObserver(new Message('B'), 789);
$subject->registerObserver(new Message('C'), 123);
$subject->registerObserver(new Message('D'), 456);
$subject->registerObserver(new Message('E'), SubjectInterface::FIRST_PRIORITY);
```

With the above example, you can expect the following output:

    E
    C
    D
    B
    A

When a subject updates its observers it begins at priority `0` (zero), and works
its way to `PHP_INT_MAX` (the lowest possible priority). If multiple observers
are registered using the same provider, they will be updated in the order that
they were registered.

### Interrupting an Update

When a subject is in the process of updating its registered observers, an
observer may interrupt the subject. An interrupt is performed by an observer
when it calls the `SubjectInterface::interruptUpdate()` method.

```php
use Phine\Observer\Exception\ReasonException;

/**
 * Simply interrupts the subject in the middle of an update.
 */
class InterruptingCow implements ObserverInterface
{
    /**
     * Interrupts the update.
     */
    public function receiveUpdate(SubjectInterface $subject)
    {
        // do some work

        $subject->interruptUpdate(
            new ReasonException('MOOOOO')
        );

        // do some final work
    }
}
```

Using the following example:

```php
// create a new subject
$subject = new Subject();

// register some observers
$subject->registerObserver(new Message('So what did the interrupt cow say?'));
$subject->registerObserver(new InterruptingCow());
$subject->registerObserver(new Message('We never get this far.'));

// notify the observers
$subject->notifyObservers();
```

You can expect the following output:

    So what did the interrupting cow say?
    PHP Fatal error:  Uncaught exception '[...]' with message 'MOOOOO' [...]
    [...]

Observers are not required to provide a reason (instance of `ReasonException`),
but it will definitely help during the debugging process if one is given.

### Collections of Subjects

There may be occasions where you will need to manage a collection of subjects.
The library provides two ways of doing so: `Collection` and `ArrayCollection`.
The `Collection` will associate an individual subject with a specific unique
identifier.

```php
use Phine\Observer\Collection;

// create a new collection
$collection = new Collection();

// register a few subjects
$collection->registerSubject('one', new Subject());
$collection->registerSubject('two', new Subject());
$collection->registerSubject('three', new Subject());
```

You can then retrieve the subjects or replace them as needed.

```php
// replace one
$collection->registerSubject('two', new Subject());

// update observers of another
$collection->getSubject('three')->notifyObservers();
```

The `ArrayCollection` class provides a leaner way of managing subject
registrations. It is an extension of the `Collection` class that supports
array access through `ArrayCollectionInterface`.

```php
use Phine\Observer\ArrayCollection;

// create a new collection
$collection = new ArrayCollection();

// register a few subjects
$collection['one'] = new Subject();
$collection['two'] = new Subject();
$collection['three'] = new Subject();
```

Like the regular `Collection` class, you can also replace and retrieve
individual subjects.

```php
// replace one
$collection['two'] = new Subject();

// update observers of another
$collection['three']->notifyObservers();
```

Documentation
-------------

You can find the [API documentation here][].

License
-------

This library is available under the [MIT license](LICENSE).

[Build Status]: https://travis-ci.org/phine/lib-observer.png?branch=master
[Coverage Status]: https://coveralls.io/repos/phine/lib-observer/badge.png
[Latest Stable Version]: https://poser.pugx.org/phine/observer/v/stable.png
[Total Downloads]: https://poser.pugx.org/phine/observer/downloads.png
[observer pattern]: http://en.wikipedia.org/wiki/Observer_pattern
[Phine Exception]: https://github.com/phine/lib-exception
[Composer]: http://getcomposer.org/
[API documentation here]: http://phine.github.io/lib-observer
