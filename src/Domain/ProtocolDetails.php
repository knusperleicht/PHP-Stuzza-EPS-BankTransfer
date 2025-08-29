<?php
declare(strict_types=1);

namespace Psa\EpsBankTransfer\Domain;

use Psa\EpsBankTransfer\Generated\Protocol\V26\EpsProtocolDetails as V26Details;
use Psa\EpsBankTransfer\Generated\Protocol\V27\EpsProtocolDetails as V27Details;

/**
 * Result of an EPS transfer initiation mapped into a compact domain object.
 *
 * Contains error code/message and the optional client redirect URL.
 */
class ProtocolDetails
{
    /** @var string */
    private $errorCode;

    /** @var string */
    private $errorMessage;

    /** @var string|null */
    private $clientRedirectUrl;

    /**
     * Create protocol details.
     *
     * @param string|null $errorCode EPS error code (e.g., "000" for no error) or null.
     * @param string|null $errorMessage Error message or null.
     * @param string|null $clientRedirectUrl Redirect URL for the client if provided by the SO.
     */
    public function __construct(string $errorCode, string $errorMessage, ?string $clientRedirectUrl = null)
    {
        $this->errorCode = $errorCode;
        $this->errorMessage = $errorMessage;
        $this->clientRedirectUrl = $clientRedirectUrl;
    }

    /**
     * Map v2.6 generated protocol details to domain model.
     */
    public static function fromV26(V26Details $details): ProtocolDetails
    {
        $bankResponse = $details->getBankResponseDetails();
        $error = $bankResponse->getErrorDetails();

        return new self(
            $error ? $error->getErrorCode() : null,
            $error ? $error->getErrorMsg() : null,
            $bankResponse->getClientRedirectUrl(),
        );
    }

    /**
     * Map v2.7 generated protocol details to domain model.
     */
    public static function fromV27(V27Details $details): ProtocolDetails
    {
        $bankResponse = $details->getBankResponseDetails();
        $error = $bankResponse->getErrorDetails();

        return new self(
            $error ? $error->getErrorCode() : null,
            $error ? $error->getErrorMsg() : null,
            $bankResponse->getClientRedirectUrl(),
        );
    }

    /**
     * EPS error code ("000" means no error), if available.
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Human-readable error message, if provided.
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    /**
     * URL to redirect the client to continue payment, if present.
     */
    public function getClientRedirectUrl(): ?string
    {
        return $this->clientRedirectUrl;
    }
}