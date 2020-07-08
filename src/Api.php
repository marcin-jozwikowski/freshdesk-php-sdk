<?php
/**
 * Created by PhpStorm.
 * User: Matt
 * Date: 20/04/2016
 * Time: 2:32 PM
 */

namespace Freshdesk;

use Freshdesk\Exceptions\AccessDeniedException;
use Freshdesk\Exceptions\ApiException;
use Freshdesk\Exceptions\AuthenticationException;
use Freshdesk\Exceptions\ConflictingStateException;
use Freshdesk\Exceptions\RateLimitExceededException;
use Freshdesk\Exceptions\UnsupportedContentTypeException;
use Freshdesk\Resources\Agent;
use Freshdesk\Resources\BusinessHour;
use Freshdesk\Resources\Category;
use Freshdesk\Resources\Comment;
use Freshdesk\Resources\Company;
use Freshdesk\Resources\Contact;
use Freshdesk\Resources\Conversation;
use Freshdesk\Resources\EmailConfig;
use Freshdesk\Resources\Forum;
use Freshdesk\Resources\Group;
use Freshdesk\Resources\Product;
use Freshdesk\Resources\SLAPolicy;
use Freshdesk\Resources\Ticket;
use Freshdesk\Resources\TimeEntry;
use Freshdesk\Resources\Topic;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Class for interacting with the Freshdesk Api
 *
 * This is the only class that should be instantiated directly. All API resources are available
 * via the relevant public properties
 *
 * @package Api
 * @author  Matthew Clarkson <mpclarkson@gmail.com>
 * @author  Marcin Jóźwikowski <marcin@jozwikowski.pl>
 */
class Api
{
    /**
     * Agent resources
     *
     * @api
     * @var Agent
     */
    public $agents;

    /**
     * Company resources
     *
     * @api
     * @var Company
     */
    public $companies;

    /**
     * Contact resources
     *
     * @api
     * @var Contact
     */
    public $contacts;

    /**
     * Group resources
     *
     * @api
     * @var Group
     */
    public $groups;

    /**
     * Ticket resources
     *
     * @api
     * @var Ticket
     */
    public $tickets;

    /**
     * TimeEntry resources
     *
     * @api
     * @var TimeEntry
     */
    public $timeEntries;

    /**
     * Conversation resources
     *
     * @api
     * @var Conversation
     */
    public $conversations;

    /**
     * Category resources
     *
     * @api
     * @var Category
     */
    public $categories;

    /**
     * Forum resources
     *
     * @api
     * @var Forum
     */
    public $forums;

    /**
     * Topic resources
     *
     * @api
     * @var Topic
     */
    public $topics;

    /**
     * Comment resources
     *
     * @api
     * @var Comment
     */
    public $comments;

    //Admin

    /**
     * Email Config resources
     *
     * @api
     * @var EmailConfig
     */
    public $emailConfigs;

    /**
     * Access Product resources
     *
     * @api
     * @var Product
     */
    public $products;

    /**
     * Business Hours resources
     *
     * @api
     * @var BusinessHour
     */
    public $businessHours;

    /**
     * SLA Policy resources
     *
     * @api
     * @var SLAPolicy
     */
    public $slaPolicies;

    /**
     * @internal
     * @var Client
     */
    protected $client;

    /**
     * @internal
     * @var string
     */
    private $baseUrl;

    /**
     * Constructs a new api instance
     *
     * @param string $apiKey
     * @param string $domain
     * @param bool   $isSubdomain
     *
     * @throws Exceptions\InvalidConfigurationException
     * @api
     */
    public function __construct(string $apiKey, string $domain, bool $isSubdomain = true)
    {
        $this->validateConstructorArgs($apiKey, $domain);

        $this->baseUrl = $isSubdomain ? sprintf('https://%s.freshdesk.com/api/v2', $domain) : $domain;
        $this->client  = new Client(['auth' => [$apiKey, 'X']]);

        $this->setupResources();
    }


    /**
     * Internal method for handling requests
     *
     * @param            $method
     * @param            $endpoint
     * @param array|null $data
     * @param array|null $query
     *
     * @return mixed|null
     * @throws ApiException
     * @throws ConflictingStateException
     * @throws RateLimitExceededException
     * @throws UnsupportedContentTypeException
     * @internal
     */
    public function request($method, $endpoint, array $data = null, array $query = null)
    {
        if (isset($data['attachments']) && !empty($data['attachments'])) {
            $options = $this->prepareMultipartFormData($data);
        } else {
            $options = ['json' => $data];
        }

        if (isset($query)) {
            $options['query'] = $query;
        }

        $url = $this->baseUrl . $endpoint;

        return $this->performRequest($method, $url, $options);
    }

    /**
     * Performs the request
     *
     * @param string $method
     * @param string $url
     * @param array  $options
     *
     * @return mixed|null
     * @throws AccessDeniedException
     * @throws ApiException
     * @throws AuthenticationException
     * @throws ConflictingStateException
     * @throws Exceptions\MethodNotAllowedException
     * @throws Exceptions\NotFoundException
     * @throws Exceptions\UnsupportedAcceptHeaderException
     * @throws Exceptions\ValidationException
     * @throws RateLimitExceededException
     * @throws UnsupportedContentTypeException
     */
    protected function performRequest(string $method, string $url, array $options): array
    {

        try {
            return json_decode($this->client->$method($url, $options)->getBody(), true);
        } catch (RequestException $e) {
            throw ApiException::create($e);
        }
    }


    /**
     * @param $apiKey
     * @param $domain
     *
     * @throws Exceptions\InvalidConfigurationException
     * @internal
     *
     */
    private function validateConstructorArgs($apiKey, $domain)
    {
        if (!isset($apiKey)) {
            throw new Exceptions\InvalidConfigurationException("API key is empty.");
        }

        if (!isset($domain)) {
            throw new Exceptions\InvalidConfigurationException("Domain is empty.");
        }
    }

    /**
     * @internal
     */
    private function setupResources()
    {
        //People
        $this->agents    = new Agent($this);
        $this->companies = new Company($this);
        $this->contacts  = new Contact($this);
        $this->groups    = new Group($this);

        //Tickets
        $this->tickets       = new Ticket($this);
        $this->timeEntries   = new TimeEntry($this);
        $this->conversations = new Conversation($this);

        //Discussions
        $this->categories = new Category($this);
        $this->forums     = new Forum($this);
        $this->topics     = new Topic($this);
        $this->comments   = new Comment($this);

        //Admin
        $this->products      = new Product($this);
        $this->emailConfigs  = new EmailConfig($this);
        $this->slaPolicies   = new SLAPolicy($this);
        $this->businessHours = new BusinessHour($this);
    }

    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    private function prepareMultipartFormData(array $data): array
    {
        $result = [];
        foreach ($data as $field => $value) {
            if ($field === 'attachments') {
                foreach ($data['attachments'] as $attachment) {
                    $result[] = $attachment;
                }
            } else {
                $result[] = [
                    'name'     => $field,
                    'contents' => $value,
                ];
            }

        }

        return ['multipart' => $result];
    }
}
