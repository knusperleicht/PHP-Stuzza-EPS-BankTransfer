<?php
declare(strict_types=1);

namespace Knusperleicht\EpsBankTransfer\Internal\V27;

use Exception;
use JMS\Serializer\SerializerInterface;
use Knusperleicht\EpsBankTransfer\Domain\BankConfirmationDetails;
use Knusperleicht\EpsBankTransfer\Domain\VitalityCheckDetails;
use Knusperleicht\EpsBankTransfer\Exceptions\CallbackResponseException;
use Knusperleicht\EpsBankTransfer\Exceptions\EpsException;
use Knusperleicht\EpsBankTransfer\Exceptions\InvalidCallbackException;
use Knusperleicht\EpsBankTransfer\Exceptions\XmlValidationException;
use Knusperleicht\EpsBankTransfer\Internal\Generated\BankList\EpsSOBankListProtocol;
use Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V27\EpsProtocolDetails;
use Knusperleicht\EpsBankTransfer\Internal\Generated\Refund\EpsRefundResponse;
use Knusperleicht\EpsBankTransfer\Internal\SoCommunicatorCore;
use Knusperleicht\EpsBankTransfer\Requests\RefundRequest;
use Knusperleicht\EpsBankTransfer\Requests\TransferInitiatorDetails;
use Knusperleicht\EpsBankTransfer\Responses\ShopResponseDetails;
use Knusperleicht\EpsBankTransfer\Utilities\XmlValidator;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Internal communicator for EPS interface version 2.7.
 *
 * Note: Functionality is intentionally not implemented yet because the official
 * XSD 2.7 is pending. All public methods throw LogicException to make the
 * limitation explicit to integrators while keeping the public API forward-compatible.
 */
class SoV27Communicator extends \Knusperleicht\EpsBankTransfer\Internal\AbstractSoCommunicator
{
    public const TRANSFER = '/transinit/eps/v2_7';
    public const VERSION = '2.7';

    protected function getVersion(): string
    {
        return self::VERSION;
    }

    protected function getTransferPath(): string
    {
        return self::TRANSFER;
    }

    protected function protocolClassFqn(): string
    {
        return EpsProtocolDetails::class;
    }

    protected function serializeTransferInitiator($transferInitiatorDetails): string
    {
        return $this->serializer->serialize($transferInitiatorDetails->toV27(), 'xml');
    }

    protected function vitalityFromProtocol($protocol): ?VitalityCheckDetails
    {
        return $protocol->getVitalityCheckDetails() ? VitalityCheckDetails::fromV27($protocol->getVitalityCheckDetails()) : null;
    }

    protected function bankConfirmationFromProtocol($protocol): ?BankConfirmationDetails
    {
        return $protocol->getBankConfirmationDetails() ? BankConfirmationDetails::fromV27($protocol) : null;
    }

    protected function shopResponseXml(ShopResponseDetails $details): string
    {
        return $this->serializer->serialize($details->toV27(), 'xml');
    }

    /**
     * Fetch the bank list using the v2.7 interface.
     *
     * Not implemented until XSD 2.7 is available.
     *
     * @param string|null $targetUrl Optional custom target URL instead of the default.
     * @return EpsSOBankListProtocol
     * @throws \LogicException Always thrown until v2.7 support is implemented.
     */
    public function getBanks(?string $targetUrl = null): EpsSOBankListProtocol
    {
        throw new \LogicException('Not implemented yet - use version 2.6');
    }

    /**
     * Send a refund request using the v2.7 interface.
     *
     * Not implemented until XSD 2.7 is available.
     *
     * @param RefundRequest $refundRequest Refund request details.
     * @param string|null $targetUrl Optional custom target URL instead of the default.
     * @return EpsRefundResponse
     * @throws \LogicException Always thrown until v2.7 support is implemented.
     */
    public function sendRefundRequest(
        RefundRequest $refundRequest,
        ?string       $targetUrl = null
    ): EpsRefundResponse
    {
        throw new \LogicException('Not implemented yet - use version 2.6');
    }
}
