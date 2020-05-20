<?php

namespace ApplicationTest\DataFixture;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use InterpretersOffice\Entity;

class CancellationReasonLoader implements FixtureInterface
{
    public function load(ObjectManager $objectManager)
    {
        $reasons = [
            'interpreter not required',
            // 'adjourned without notice',
            'belatedly adjourned',
            'defendant not produced',
            'forçe majeure',
            'party did not appear',
            'other',
            'unknown',
        ];
        foreach ($reasons as $r) {
            $e = (new Entity\ReasonForCancellation())->setReason($r);
            $objectManager->persist($e);
        }

        $objectManager->flush();
    }
}
