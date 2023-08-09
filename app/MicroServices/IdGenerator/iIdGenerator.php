<?php

namespace App\MicroServices\IdGenerator;

/**
 * | Created On-22-03-2023 
 * | Created By-Anshu Kumar
 * | Interface for the Id Generation Service
 */
interface iIdGenerator
{
    public function generate(): string;
}
