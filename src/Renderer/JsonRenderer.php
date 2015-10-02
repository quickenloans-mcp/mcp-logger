<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace MCP\Logger\Renderer;

use DateTime;
use MCP\DataType\IPv4Address;
use MCP\DataType\Time\TimePoint;
use MCP\Logger\MessageInterface;
use MCP\Logger\RendererInterface;

/**
 * Data is formatted differently for JSON, which is meant to be sent to splunk.
 *
 * @see https://confluence/display/CORE/Logging+to+Splunk
 *
 * @internal
 */
class JsonRenderer implements RendererInterface
{
    /**
     * @param MessageInterface $message
     *
     * @return string
     */
    public function __invoke(MessageInterface $message)
    {
        $data = [];

        // Required
        $this->addProperty($data, 'AppID', $this->sanitizeInteger($message->applicationId()));
        $this->addProperty($data, 'Created', $this->sanitizeTime($message->createTime()));
        $this->addProperty($data, 'Environment', $this->sanitizeString(strtolower($message->environment())));

        $this->addProperty($data, 'Level', $this->sanitizeString(strtolower($message->level())));
        $this->addProperty($data, 'Message', $this->sanitizeString($message->message()));
        $this->addProperty($data, 'UserIsDisrupted', $this->sanitizeBoolean($message->isUserDisrupted()));

        $this->addProperty($data, 'ServerIP', $this->sanitizeIP($message->machineIPAddress()));
        $this->addProperty($data, 'ServerHostname', $this->sanitizeString($message->machineName()));

        // Optional
        $this->addOptionalProperty($data, 'Exception', $this->sanitizeString($message->exceptionData()));

        $this->addOptionalProperty($data, 'AffectedSystem', $this->sanitizeString($message->affectedSystem()));
        $this->addOptionalProperty($data, 'Category', $this->sanitizeInteger($message->categoryId()));

        $this->addOptionalProperty($data, 'Referrer', $this->sanitizeString($message->referrer()));
        $this->addOptionalProperty($data, 'Method', $this->sanitizeString($message->requestMethod()));
        $this->addOptionalProperty($data, 'URL', $this->sanitizeString($message->url()));
        $this->addOptionalProperty($data, 'UserAgent', $this->sanitizeString($message->userAgentBrowser()));

        $this->addOptionalProperty($data, 'UserID', $this->sanitizeInteger($message->userCommonId()));
        $this->addOptionalProperty($data, 'UserDisplayName', $this->sanitizeString($message->userDisplayName()));
        $this->addOptionalProperty($data, 'UserName', $this->sanitizeString($message->userName()));
        $this->addOptionalProperty($data, 'UserIP', $this->sanitizeIP($message->userIPAddress()));

        $this->addExtendedProperties($data, $message->extendedProperties());

        return json_encode($data);
    }

    /**
     * @return string
     */
    public function contentType()
    {
        return 'application/json';
    }

    /**
     * @param array $data
     * @param mixed[] $properties
     *
     * @return null
     */
    protected function addExtendedProperties(array &$data, $properties)
    {
        if (!is_array($properties)) {
            $properties = [];
        }

        $extended = [];
        foreach ($properties as $k => $prop) {
            $extended[$k] = $this->sanitizeString($prop);
        }

        if ($extended) {
            $this->addProperty($data, 'Properties', $extended);
        }
    }

    /**
     * @param array $data
     * @param string $name
     * @param mixed $value
     *
     * @return null
     */
    protected function addOptionalProperty(array &$data, $name, $value)
    {
        if ($value === null || $value === '') {
            return;
        }

        $this->addProperty($data, $name, $value);
    }

    /**
     * @param array $data
     * @param string $name
     * @param mixed $value
     *
     * @return null
     */
    protected function addProperty(array &$data, $name, $value)
    {
        $data[$name] = $value;
    }

    /**
     * @param boolean $value
     *
     * @return boolean
     */
    protected function sanitizeBoolean($value)
    {
        return (bool) $value;
    }

    /**
     * @param int|string $value
     *
     * @return int
     */
    protected function sanitizeInteger($value)
    {
        if ($res = filter_var($value, FILTER_SANITIZE_NUMBER_INT)) {
            return (int) $res;
        }
    }

    /**
     * @param IPv4Address|null $value
     *
     * @return string|null
     */
    protected function sanitizeIP($value)
    {
        if ($value instanceof IPv4Address) {
            return $value->asString();
        }

        return null;
    }

    /**
     * @param int|string $value
     *
     * @return string
     */
    protected function sanitizeString($value)
    {
        return filter_var((string) $value, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_HIGH);
    }

    /**
     * @param TimePoint|null $value
     *
     * @return string|null
     */
    protected function sanitizeTime($value)
    {
        if ($value instanceof TimePoint) {
            return $value->format('Y-m-d\TH:i:s\Z', 'UTC');
        }

        return null;
    }
}
