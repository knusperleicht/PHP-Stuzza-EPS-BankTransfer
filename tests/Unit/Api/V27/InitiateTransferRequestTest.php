<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Tests\Api\V27;

use Externet\EpsBankTransfer\Tests\Helper\SoV27CommunicatorTestTrait;
use PHPUnit\Framework\TestCase;

class InitiateTransferRequestTest extends TestCase
{
    use SoV27CommunicatorTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCommunicator();
    }


    //TODO add tests when xsd's are available

}
