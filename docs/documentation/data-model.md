---
layout: default
title: data model | documentation | InterpretersOffice.org
---

<h2 class="my-4">The entity-relationship model</h2>

The entity-relationship model, as represented in the relational database structure, attempts to model the real-world entities that 
court interpreters deal with in running their office: contract interpreters, staff interpreters, judges, languages, places, types of proceedings, 
users (of the application), and more generally, people who contact the Interpreters to request services.

Like the real world, the entity-relationship model of <span class="text-monospace">InterpretersOffice</span> is complex.

<span class="text-monospace">InterpretersOffice</span> is intended for use with MySQL or MariaDB for production,
but also includes a suite of tests that run against SQLite3. The database consists 
of the following tables, most of which are mapped to Doctrine entity classes:

<pre class="text-white bg-dark p-2">
  <code>
    MariaDB [office]> show tables;
    +------------------------+
    | Tables_in_office       |
    +------------------------+
    | anonymous_judges       |
    | app_event_log          |
    | banned                 |
    | cancellation_reasons   |
    | category               |
    | clerks_judges          |
    | court_closings         |
    | defendant_names        |
    | defendants_events      |
    | defendants_requests    |
    | docket_annotations     |
    | event_categories       |
    | event_types            |
    | events                 |
    | hats                   |
    | holidays               |
    | interpreters           |
    | interpreters_events    |
    | interpreters_languages |
    | judge_flavors          |
    | judges                 |
    | language_credentials   |
    | languages              |
    | location_types         |
    | locations              |
    | motd                   |
    | motw                   |
    | people                 |
    | requests               |
    | roles                  |
    | rotation_substitutions |
    | rotations              |
    | task_rotation_members  |
    | tasks                  |
    | users                  |
    | verification_tokens    |
    | view_locations         |
    +------------------------+

  </code>
</pre>

### The `events` table

The **events** entity is central; it represents an event that requires the services of one or more 
court interpreters. The core of <span class="text-monospace">InterpretersOffice</span> is CRUD operations 
on these entities. It is also the most complex entity, with several attributes that are entities unto 
themselves.

<pre class="text-white bg-dark pl-2 py-1">
  <code>
CREATE TABLE `events` (
    `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
    `language_id` smallint(5) unsigned NOT NULL,
    `judge_id` smallint(5) unsigned DEFAULT NULL,
    `submitter_id` smallint(5) unsigned DEFAULT NULL,
    `location_id` smallint(5) unsigned DEFAULT NULL,
    `date` date NOT NULL,
    `time` time DEFAULT NULL,
    `end_time` time DEFAULT NULL,
    `docket` varchar(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
    `comments` varchar(600) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
    `admin_comments` varchar(600) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
    `created` datetime NOT NULL,
    `modified` datetime DEFAULT NULL,
    `event_type_id` smallint(5) unsigned NOT NULL,
    `created_by_id` smallint(5) unsigned NOT NULL,
    `anonymous_judge_id` smallint(5) unsigned DEFAULT NULL,
    `anonymous_submitter_id` smallint(5) unsigned DEFAULT NULL,
    `cancellation_reason_id` smallint(5) unsigned DEFAULT NULL,
    `modified_by_id` smallint(5) unsigned DEFAULT NULL,
    `submission_date` date NOT NULL,
    `submission_time` time DEFAULT NULL,
    `deleted` tinyint(3) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `IDX_5387574A82F1BAF4` (`language_id`),
    KEY `IDX_5387574AB7D66194` (`judge_id`),
    KEY `IDX_5387574A919E5513` (`submitter_id`),
    KEY `IDX_5387574A64D218E` (`location_id`),
    KEY `IDX_5387574AFF915C63` (`anonymous_judge_id`),
    KEY `IDX_5387574A61A31DAE` (`anonymous_submitter_id`),
    KEY `IDX_5387574A8453C906` (`cancellation_reason_id`),
    KEY `IDX_5387574AB03A8386` (`created_by_id`),
    KEY `IDX_5387574A99049ECE` (`modified_by_id`),
    KEY `IDX_5387574A401B253C` (`event_type_id`),
    KEY `docket_idx` (`docket`),
    CONSTRAINT `FK_5387574A401B253C` FOREIGN KEY (`event_type_id`) REFERENCES `event_types` (`id`),
    CONSTRAINT `FK_5387574A61A31DAE` FOREIGN KEY (`anonymous_submitter_id`) REFERENCES `hats` (`id`),
    CONSTRAINT `FK_5387574A64D218E` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
    CONSTRAINT `FK_5387574A82F1BAF4` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`),
    CONSTRAINT `FK_5387574A8453C906` FOREIGN KEY (`cancellation_reason_id`) REFERENCES `cancellation_reasons` (`id`),
    CONSTRAINT `FK_5387574A919E5513` FOREIGN KEY (`submitter_id`) REFERENCES `people` (`id`),
    CONSTRAINT `FK_5387574A99049ECE` FOREIGN KEY (`modified_by_id`) REFERENCES `users` (`id`),
    CONSTRAINT `FK_5387574AB03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`),
    CONSTRAINT `FK_5387574AB7D66194` FOREIGN KEY (`judge_id`) REFERENCES `judges` (`id`),
    CONSTRAINT `FK_5387574AFF915C63` FOREIGN KEY (`anonymous_judge_id`) REFERENCES `anonymous_judges` (`id`)
  ) ENGINE=InnoDB AUTO_INCREMENT=122912 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
</code>
</pre>



An *event* has attributes that include, among other things: date, time, language, judge, docket number, location, event-type, 
and the reason for a possible cancellation. 

The *date* and *time* columns refer to the date and time the event is scheduled to take place. They are stored in separate 
fields rather than a single timestamp for a reason:  sometimes users need to create a date with a null time because they know the date, 
but not the time at which the event will take place, so 
they need to create a placeholder event and add the time later. 

The *end_time* is a field that users with maximum adminstrative privileges can enable or disable. When enabled, it is used to store the 
time at which an event actually ended -- not a speculative or aspirational time at which it is hoped or predicted that it will end. 
This feature was added when in the Southern District of New York the interpreters were asked to keep track of the hours and minutes spent 
on interpreting assignments, in addition to existing reporting requirements. It has proven useful, however as an indicator of 
whether an event is over or still in progress. On busy days it helps administrators keep track of where interpreters are (or at least,
where they are not).

The point of the *language_id* column is self-evident. Note that on some rare occasions, a single court proceeding requires interpreters of more than one language. 
In such cases a separate event is created for each language.

The *judge_id* field is set to a non-null value when the identity of the judge is significant, which normally means it's 
a District Court Judge as opposed to Magistrate. Many proceedings take place before the on-duty Magistrate, and 
<span class="text-monospace">InterpretersOffice</span> does not bother to record the Magistrate's identity because it is 
basically random and does not help to identify the case in a longitudinal sense. In case of a generic or anonymous judge, 
the *anonymous_judge_id* field is populated instead. It's worth noting that one and only one of either the 
*judge_id* or the *anonymous_judge_id* must be null.

The *docket* column contains a docket number (string) in a consistent format that is enforced by the application.

The *location_id* column refers to the place where the event takes place -- for in-court proceedings, a courtroom. 

The *event-type* (represented by the *event_type_id* column) refers to the name of the court proceeding (e.g., pretrial conference)
or ancillary event (e.g., attorney-client interview.)

Belated cancellation is such a common occurrence that <span class="text-monospace">InterpretersOffice</span> also treats the 
reasons for a cancellation as an attribute *cancellation_reason_id*, which is left as null if not applicable.

The *comments* and *admin_comments* are for just that: writing any comments or observations that are relevant to 
the event. The difference is that *comments* are intended to be viewed by anyone who has read access to the 
interpreters schedule, i.e., any court employee and any contract interpreter who is on site; 
*admin_comments*  are intended only for the eyes of Interpreters Office staff.

The columns *submitter_id* and *anonymous_submitter_id* refer to the person (or type of person) who submitted 
the request for an interpreter. The former points to a record in the *people* table; it is populated when the 
identity of the person is required (more about that later). The latter points to the generic type or job description 
of person submitting the request, and is used when the identity of the person making the request 
is not of interest. It points to a record in the *hats* table. The reasoning here is that when a request 
is submitted for certain types of event-types, such as in-court proceedings and USPO PSI interviews, the identity 
of the submitter is useful, if not essential, in order to carry out the assignment or negotiate details around 
it. In other cases -- e.g., when things are busy and the phone is ringing with requests for interpreters for intake interviews 
for new arrests -- the name of the person calling is not particularly important, only the department -- e.g., 
Pretrial or Magistrates.

As with the *judge_id* and *anonymous_judge_id* columns, one and only one of either the  *submitter_id* and *anonymous_submitter_id* must be null. 
Note that in both of these cases, in addition to the validation rules applied by the form handling process, the <span class="text-monospace">InterpretersOffice\Entity\Event</span> class has a 
a [Doctrine lifecycle callback](https://www.doctrine-project.org/projects/doctrine-orm/en/2.7/reference/events.html#lifecycle-callbacks) to enforce this 
constraint.

The columns *created*, *modified*, *created_by_id*, and *modified_by_id* are metadata columns for recording who created the event record and when, who was the 
last user to update it and when. Note that the primary-key relationship of *created_by_id* and *modified_by_id* is with the **users** table, rather than with **people**. The reason 
is that the data is always manipulated by users, meaning people who are both authenticated and authorized, whereas a request for an interpreter can be and often is 
received from a person who does not have any user account in this system. (Of course, the **users** and **people** tables have a primary key relationship. 
A user is a person, but not necessarily vice-versa. More about this in the section on authentication and authorization.)

The column *deleted* is a boolean flag indicating whether the record has been deleted by a user -- soft deletion, in other words. This feature was added 
by popular demand. At present there is no facility for viewing or undeleting records that have been soft-deleted. It merely prevents users from deleting 
event records and provides a way to restore them, if only by use of the command-line client.

### entities in a 1-M relationship with `events`

**anonymous_judges**, **judges**, **event_types**,  **languages**, **locations**, **cancellation_reasons**, **people**, **hats** and **users** are all entities with which 
the **events** table has a one-to-many relationship. As mentioned earlier, an event entity has either a named judge or an anonymous, generic judge; it also has a *location* 
where it takes place (possibly null), a reason why it was cancelled (*cancellation_reason_id* also possibly null), a language, and an event-type. It also has either a 
named or anonymous/generic submitter. The former is related to the *people* table; the latter, to the *hats* table. 

At this writing, the *hats*, *cancellation_reasons* and *anonymous_judges* cannot be updated by the user; they are basically hard-coded at installation time (this may change 
in a future version). The remaining entities are per force exposed to the users, who have to maintain them. In other words, the users maintain their own lists of languages, 
locations, judges, and event types, using interfaces provided by <span class="text-monospace">InterpretersOffice</span>. These end up populating the options of the 
select elements of the form used for creating and updating events.

Some of the entities that are in a M-1 relationship with *events* are themselves what we might call compound entities:  they have attributes which are entities unto themselves. One example 
is the <span class="text-monospace">InterpretersOffice\Entity\Location</span>, which has an attribute *location_type* which is represented by the <span class="text-monospace">InterpretersOffice\Entity\LocationType</span>
entity, providing a basic taxonomy for location entities.  A location can be one of several types:

<pre class="text-white bg-dark pl-2">
<code>
MariaDB [office]> select * from location_types;
+----+--------------------------+----------+
| id | type                     | comments |
+----+--------------------------+----------+
|  1 | courtroom                |          |
|  2 | jail                     |          |
|  3 | holding cell             |          |
|  4 | US Probation office      |          |
|  5 | Pretrial Services office |          |
|  6 | interpreters office      |          |
|  7 | courthouse               |          |
|  8 | public area              |          |
+----+--------------------------+----------+
</code>
</pre>

### entities in a M-M relationship with `events`

One of the most important pieces of the model is of course the *interpreter.* An event can have multiple interpreters, and interpreters can be assigned, over time, to 
thousands of events. Accordingly, there is an association class <span class="text-monospace">InterpretersOffice\Entity\InterpreterEvent</span> which is mapped to a join table 
called **interpreters_events.** In addition to the IDs of the interpreter and the event, this table holds metadata about the assignment -- who assigned the interpreter to the event, and when -- as well as a 
field indicating whether the interpreter has been sent a confirmation notice via email.

For statistical reporting and other administrative purposes, a court interpreting "event" is frequently a misnomer. What we really are talking about, in many 
cases, is *interpreter-events*. A sentencing hearing involving one non-English language in front of a judge is represented by one row in the events table, but 
there may well be two rows in *interpreters_events* that are related to that event record. In such a case, for reporting purposes, that single court proceeding is 
counted as two interpreter-events, not one. 

Another critically important element of the model -- the *raison d'être* of the entire court interpreting profession -- is the person who does not speak the language of 
the Court and for whom the interpreters are interpreting. For lack of a better term, <span class="text-monospace">InterpretersOffice</span> labels them **defendants** 
(and stores their surnames and given names in an eponymous table). In federal court interpreting, the non-English speaker is usually, 
though not always, a defendant in a criminal proceeding. Although they are in fact people, the data about them is stored in a separate
from the *people table* for a couple of good reasons, the most compelling of which is that unlike systems used by the immigration or prison authorities,
 <span class="text-monospace">InterpretersOffice</span> *does not try to track the actual identity of defendants.*  
 The names of defendants involved in court interpreted events are primarily used as an attribute to  help distinguish one event from another and 
 avoid under- or over-counting. We therefore "recycle" names when they recur, rather than trying to maintain a separate 
 record for each and every individual for whom the interpreters interpret.

 Hereagain, as with the interpreters, an event can have multiple defendants and defendants are nearly always associated with multiple events.
 Hence the many-to-many relationship. The *defendants_events* table has only the two columns *event_id* and *defendant_id* and no corresponding association class. 
 The `$defendants` property is simply mapped in the  <span class="text-monospace">InterpretersOffice\Entity\Event</span> class as a many-to-many 
 relationship with the <span class="text-monospace">InterpretersOffice\Entity\Defendant</span> entity.

[more to come]
<div>
<!-- 
  div 
 | app_event_log          |
 | banned                 |
 | cancellation_reasons   |
 | category               |
 | clerks_judges          |
 | court_closings         |
 | defendant_names        |
 | defendants_events      |
 | defendants_requests    |
 | docket_annotations     |
 | event_categories       |
 | event_types            |
 | events                 |
 | hats                   |
 | holidays               |
 | interpreters           |
 | interpreters_events    |
 | interpreters_languages |
 | judge_flavors          |
 | judges                 |
 | language_credentials   |
 | languages              |
 | location_types         |
 | locations              |
 | motd                   |
 | motw                   |
 | people                 |
 | requests               |
 | roles                  |
 | rotation_substitutions |
 | rotations              |
 | task_rotation_members  |
 | tasks                  |
 | users                  |
 | verification_tokens    |
 | view_locations         |
 +------------------------+

 -->

</div>
 