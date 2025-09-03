<?php
declare(strict_types=1);

namespace Knusperleicht\EpsBankTransfer\Tests\Domain;

use Knusperleicht\EpsBankTransfer\Domain\Bank;
use Knusperleicht\EpsBankTransfer\Domain\BankList;
use PHPUnit\Framework\TestCase;
use Knusperleicht\EpsBankTransfer\Internal\Generated\BankList\EpsSOBankListProtocol;

class BankListTest extends TestCase
{
    public function testGetBanksReturnsInjectedArray(): void
    {
        $banks = [
            new Bank('GAWIATW1XXX', 'Hypo Tirol', 'https://eps.example/hypo', 'AT'),
            new Bank('BAWATW1XXX', 'BAWAG'),
        ];

        $list = new BankList($banks);
        $this->assertSame($banks, $list->getBanks());
    }

    public function testFromV26MapsGeneratedProtocol(): void
    {
        $this->ensureProtocolStubExists();
        $proto = new class extends EpsSOBankListProtocol {
            public function __construct()
            {
            }

            public function getBank(): array
            {
                return [
                    new class {
                        public function getBic()
                        {
                            return 'GAWIATW1XXX';
                        }

                        public function getBezeichnung()
                        {
                            return 'Hypo Tirol';
                        }

                        public function getEpsUrl()
                        {
                            return 'https://eps.example/hypo';
                        }

                        public function getLand()
                        {
                            return 'AT';
                        }

                        public function getZahlungsweiseNat()
                        {
                            return [];
                        }

                        public function getZahlungsweiseInt()
                        {
                            return null;
                        }

                        public function getApp2app()
                        {
                            return false;
                        }
                    },
                    new class {
                        public function getBic()
                        {
                            return 'BAWATW1XXX';
                        }

                        public function getBezeichnung()
                        {
                            return 'BAWAG';
                        }

                        public function getEpsUrl()
                        {
                            return null;
                        }

                        public function getLand()
                        {
                            return '';
                        }

                        public function getZahlungsweiseNat()
                        {
                            return [];
                        }

                        public function getZahlungsweiseInt()
                        {
                            return null;
                        }

                        public function getApp2app()
                        {
                            return false;
                        }
                    },
                ];
            }
        };

        $list = BankList::from($proto);
        $banks = $list->getBanks();

        $this->assertCount(2, $banks);
        $this->assertInstanceOf(Bank::class, $banks[0]);
        $this->assertSame('GAWIATW1XXX', $banks[0]->getBic());
        $this->assertSame('Hypo Tirol', $banks[0]->getName());
        $this->assertSame('https://eps.example/hypo', $banks[0]->getUrl());
        $this->assertSame('AT', $banks[0]->getCountryCode());

        $this->assertSame('BAWATW1XXX', $banks[1]->getBic());
        $this->assertSame('BAWAG', $banks[1]->getName());
        $this->assertNull($banks[1]->getUrl());
        $this->assertSame('', $banks[1]->getCountryCode());
    }

    private function ensureProtocolStubExists(): void
    {
        if (!class_exists('Knusperleicht\\EpsBankTransfer\\Internal\\Generated\\BankList\\EpsSOBankListProtocol')) {
            eval('namespace Knusperleicht\\EpsBankTransfer\\Internal\\Generated\\BankList; abstract class EpsSOBankListProtocol { public function __construct() {} public abstract function getBank(): array; }');
        }
    }
}