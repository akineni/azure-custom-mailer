<?php

/**
 * Class AzureCustomMailer
 *
 * This class provides functionality to send emails using Azure services.
 * @author Akinlonu Eniola Oluwatobi.
 */
class AzureCustomMailer {

    /**
     * The API version used for sending emails.
     */
    private const API_VERSION = '2023-03-31';

    /**
     * The path and query used in the API request.
     */
    private const PATH_AND_QUERY = '/emails:send?api-version=' . AzureCustomMailer::API_VERSION;

    /**
     * Determines if the email content is HTML.
     *
     * @var bool
     */
    private bool $html = false;

    /**
     * The email address of the sender.
     *
     * @var string
     */
    private string $from;

    /**
     * An array of email addresses for the recipients.
     *
     * @var array
     */
    private array $to = array();

    /**
     * An array of email addresses for the CC (carbon copy) recipients.
     *
     * @var array
     */
    private array $cc = array();

    /**
     * An array of email addresses for the BCC (blind carbon copy) recipients.
     *
     * @var array
     */
    private array $bcc = array();

    /**
     * An array of email addresses for the reply-to recipients.
     *
     * @var array
     */
    private array $replyTo = array();

    /**
     * An array of file attachments for the email.
     *
     * @var array
     */
    private array $attachments = array();

    /**
     * An array of custom headers for the email.
     *
     * @var array
     */
    private array $headers = array();

    /**
     * The operation ID of the last email sent.
     *
     * @var string
     */
    private string $operationId = '';

    /**
     * The error object containing information about the last error, if any.
     *
     * @var object|null
     */
    private ?object $error;

    /**
     * The cURL handle used for making HTTP requests.
     */
    private $cURLHandle;

    /**
     * The subject of the email.
     *
     * @var string
     */
    public string $azureSubject;

    /**
     * The body of the email.
     *
     * @var string
     */
    public string $azureBody;

    /**
     * The Azure endpoint for sending emails.
     *
     * @var string
     */
    public string $azureEndpoint;

    /**
     * The Azure access key for authentication.
     *
     * @var string
     */
    public string $azureAccessKey;

    /**
     * AzureCustomMailer constructor.
     *
     * Initializes the cURL handle for making HTTP requests.
     */
    function __construct() {
        $this->cURLHandle = curl_init();
    }

    /**
     * Creates a new instance of AzureCustomMailer.
     *
     * @param string $endPoint   The Azure endpoint for sending emails.
     * @param string $accessKey  The Azure access key for authentication.
     * @return AzureCustomMailer The newly created AzureCustomMailer instance.
     */
    static function create($endPoint, $accessKey): self {
        $azureCustomMailer = new AzureCustomMailer();
        $azureCustomMailer->azureEndpoint = $endPoint;
        $azureCustomMailer->azureAccessKey = $accessKey;
        return $azureCustomMailer;
    }

    /**
     * Formats the signature string for signing the HTTP request.
     *
     * @param array $signatureArgs The signature parameters.
     * @return string              The formatted signature string.
     */
    private function formatSignatureString(array $signatureArgs): string {
        /*
        *   Structure the Signature Parameters
        *   https://learn.microsoft.com/en-us/rest/api/communication/authentication#signing-an-http-request
        */
        return "{$signatureArgs['verb']}\n{$signatureArgs['uriPathAndQuery']}\n{$signatureArgs['timestamp']};{$signatureArgs['host']};{$signatureArgs['contentHash']}";
    }

    /**
     * Encodes the signature string.
     *
     * @param string $stringToSign The string to sign.
     * @return string              The encoded signature.
     */
    private function encodeSignature(string $stringToSign): string {
        /*
        *   Sign the signature string
        *   https://learn.microsoft.com/en-us/rest/api/communication/authentication#signing-an-http-request
        */
        $hmac = hash_hmac('sha256', $stringToSign, base64_decode($this->azureAccessKey), true);
        return base64_encode($hmac);
    }

    /**
     * Retrieves the authority from the given URI.
     *
     * @param string $uri The URI.
     * @return string     The authority.
     */
    private function getUriAuthority(string $uri): string {
        $authority = parse_url($uri, PHP_URL_HOST);

        /*
        *   Authority may contain port number too
        *   https://stackoverflow.com/questions/2366270/what-does-uri-has-an-authority-component-mean
        *   https://learn.microsoft.com/en-us/dotnet/api/system.uri.authority?view=net-7.0
        */
        if(parse_url($uri, PHP_URL_PORT)) $authority .= ':' . parse_url($uri, PHP_URL_PORT);
        return $authority;
    }

    /**
     * Adds an address to the specified field (to, cc, or bcc).
     *
     * @param string $field       The field to add the address to.
     * @param string $address     The email address.
     * @param string $displayName The display name (optional).
     */
    private function addAddress(string $field, string $address, string $displayName = ''): void {
        array_push(
            $this->$field,
            array(
                'address' => $address,
                'displayName' => $displayName
            )
        );
    }

    /**
     * Sets whether the email content is HTML or plain text.
     *
     * @param bool $isHtml True if the email content is HTML, false otherwise.
     */
    function azureIsHTML(bool $isHtml): void {
        $this->html = $isHtml;
    }

    /**
     * Sets the sender of the email.
     *
     * @param string $address     The email address of the sender.
     * @param string $displayName The display name of the sender (optional).
     */
    function azureSetFrom(string $address, string $displayName = ''): void {
        $this->from = $address;
    }

    /**
     * Adds a recipient to the "to" field.
     *
     * @param string $address     The email address of the recipient.
     * @param string $displayName The display name of the recipient (optional).
     */
    function azureAddAddress(string $address, string $displayName = ''): void {
        $this->addAddress('to', $address, $displayName);
    }

    /**
     * Adds a recipient to the "reply-to" field.
     *
     * @param string $address     The email address of the recipient.
     * @param string $displayName The display name of the recipient (optional).
     */
    function azureAddReplyTo(string $address, string $displayName = ''): void {
        $this->addAddress('replyTo', $address, $displayName);
    }

    /**
     * Adds a recipient to the "cc" field.
     *
     * @param string $address     The email address of the recipient.
     * @param string $displayName The display name of the recipient (optional).
     */
    function azureAddCC(string $address, string $displayName = ''): void {
        $this->addAddress('cc', $address, $displayName);
    }

    /**
     * Adds a recipient to the "bcc" field.
     *
     * @param string $address     The email address of the recipient.
     * @param string $displayName The display name of the recipient (optional).
     */
    function azureAddBCC(string $address, string $displayName = ''): void {
        $this->addAddress('bcc', $address, $displayName);
    }

    /**
     * Adds an attachment to the email.
     *
     * @param string $path The path to the attachment file.
     * @param string $type The content type of the attachment (optional).
     */
    function azureAddAttachment(string $path, string $type = ''): void {
        array_push(
            $this->attachments,
            array(
                'name' => $path,
                'contentType' => empty($type) ? mime_content_type($path) : $type,
                'contentInBase64' => base64_encode(file_get_contents($path))
            )
        );
    }

    /**
     * Adds a custom header to the email.
     *
     * @param string $name  The name of the header.
     * @param string $value The value of the header.
     */
    function azureAddCustomHeader(string $name, string $value): void {
        $this->headers[$name] = $value;
    }

    /**
     * Clears all addresses in the "to" field.
     */
    function azureClearAddresses(): void {
        $this->to = array();
    }

    /**
     * Clears all addresses in the "reply-to" field.
     */
    function azureClearReplyTos(): void {
        $this->replyTo = array();
    }

    /**
     * Clears all addresses in the "cc" field.
     */
    function azureClearCCs(): void {
        $this->cc = array();
    }

    /**
     * Clears all addresses in the "bcc" field.
     */
    function azureClearBCCs(): void {
        $this->bcc = array();
    }

    /**
     * Clears all attachments.
     */
    function azureClearAttachments(): void {
        $this->attachments = array();
    }

    /**
     * Clears all recipients (addresses in the "to", "cc", and "bcc" fields).
     */
    function azureClearAllRecipients(): void {
        $this->azureClearAddresses();
        $this->azureClearCCs();
        $this->azureClearBCCs();
    }

    /**
     * Clears all custom headers.
     */
    function azureClearCustomHeaders(): void {
        $this->headers = array();
    }

    /**
     * Resets the state of the AzureCustomMailer instance.
     */
    function azureReset(): void {
        $this->azureClearAllRecipients();
        $this->azureClearReplyTos();
        $this->azureClearCustomHeaders();
        $this->azureClearAttachments();
        $this->html = false;
        $this->from = '';
        $this->operationId = '';
        $this->azureSubject = '';
        $this->azureBody = '';
        $this->error = null;
    }

    /**
     * Sends the email using Azure.
     *
     * @return bool True if the email was sent successfully, false otherwise.
     */
    function azureSend(): bool {

        $content = array('subject' => $this->azureSubject);
        if($this->html)
            $content['html'] = $this->azureBody;
        else
            $content['plainText'] = $this->azureBody;

        // Structure the request body: for both signing and delivery
        $requestBody = array(
            'senderAddress' => $this->from,
            'content' => $content,
            'recipients' => array(
                'to' => $this->to,
                'cc' => $this->cc,
                'bcc' => $this->bcc
            ),
            'headers' => empty($this->headers) ? null : $this->headers,
            'replyTo' => $this->replyTo,
            'attachments' => $this->attachments,
            'userEngagementTrackingDisabled' => 'false'
        );

        // Structure signature parameters
        $signatureParams = array(
            'verb' => 'POST',
            'uriPathAndQuery' => AzureCustomMailer::PATH_AND_QUERY,
            'timestamp' => gmdate('D, d M Y H:i:s T', time()),
            'host' => $this->getUriAuthority($this->azureEndpoint),
            'contentHash' => base64_encode(hash('sha256', json_encode($requestBody), true))
        );
        
        // Sign the request
        $stringToSign = $this->formatSignatureString($signatureParams);
        $signature = $this->encodeSignature($stringToSign);
        
        curl_reset($this->cURLHandle);
        curl_setopt_array(
            $this->cURLHandle,
            array(
                CURLOPT_URL => $this->azureEndpoint . AzureCustomMailer::PATH_AND_QUERY,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => array(
                    "x-ms-date: {$signatureParams['timestamp']}",
                    "x-ms-content-sha256: {$signatureParams['contentHash']}",
                    "host: {$signatureParams['host']}",
                    "Authorization: HMAC-SHA256 SignedHeaders=x-ms-date;host;x-ms-content-sha256&Signature={$signature}",
                    "Content-Type: application/json"
                ),
                CURLOPT_POSTFIELDS => json_encode($requestBody)
            )
        );

        $response = curl_exec($this->cURLHandle);
        $httpCode = curl_getinfo($this->cURLHandle, CURLINFO_HTTP_CODE);

        if($httpCode === 202)
            $this->operationId = json_decode($response)->id;
        else{
            // Possible errors could be from 2 sources: local cURL HTTP request and Azure server
            if(curl_error($this->cURLHandle)) {
                $this->error = (object) array(
                    'message' => curl_error($this->cURLHandle),
                    'code' => curl_errno($this->cURLHandle)
                );
            }else
                $this->error = json_decode($response)->error;
        }

        return $httpCode === 202;
    }

    /**
     * Retrieve the status of a sent email operation.
     *
     * @param string $operationId The unique identifier of the email operation.
     * @return object|null The response object containing the status of the operation, or null on failure.
     */
    function azureGetSendStatus($operationId): ?object {
        $statusEndPoint = "/emails/operations/{$operationId}?api-version=" . AzureCustomMailer::API_VERSION;

        $signatureParams = array(
            'verb' => 'GET',
            'uriPathAndQuery' => $statusEndPoint,
            'timestamp' => gmdate('D, d M Y H:i:s T', time()),
            'host' => $this->getUriAuthority($this->azureEndpoint),
            'contentHash' => base64_encode(hash('sha256', '', true))
        );
    
        $stringToSign = $this->formatSignatureString($signatureParams);
        $signature = $this->encodeSignature($stringToSign);
        
        curl_reset($this->cURLHandle);
        curl_setopt_array(
            $this->cURLHandle,
            array(
                CURLOPT_URL => $this->azureEndpoint . $statusEndPoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => array(
                    "x-ms-date: {$signatureParams['timestamp']}",
                    "x-ms-content-sha256: {$signatureParams['contentHash']}",
                    "host: {$signatureParams['host']}",
                    "Authorization: HMAC-SHA256 SignedHeaders=x-ms-date;host;x-ms-content-sha256&Signature={$signature}",
                    "Content-Type: application/json"
                )
            )
        );

        $response = curl_exec($this->cURLHandle);
        if(curl_getinfo($this->cURLHandle, CURLINFO_HTTP_CODE) !== 202){
            // Handle cURL error and receive Azure's in $response object ($response->error)
            if(curl_error($this->cURLHandle)){
                return (object) array(
                    'message' => curl_error($this->cURLHandle),
                    'code' => curl_errno($this->cURLHandle)
                );
            } // else it's azure
        }

        return json_decode($response);
    }

    /**
     * Check if there was an error during the email sending process.
     *
     * @return bool True if an error occurred, false otherwise.
     */
    function azureError(): bool {
        return !is_null($this->error);
    }

    /**
     * Retrieves information about the last error, if any.
     *
     * @return object|null The error object.
     */
    function azureGetError(): ?object {
        return $this->error;
    }

    /**
     * Retrieves the operation ID of the last email sent.
     *
     * @return string The operation ID.
     */
    function getOperationId(): string {
        return $this->operationId;
    }

    /**
     * Closes the cURL handle.
     */
    function __destruct() {
        curl_close($this->cURLHandle);
    }

}
 
?>