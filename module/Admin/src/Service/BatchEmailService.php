<?php /** module/Admin/src/Service/BatchEmailService.php */

declare(strict_types=1);

namespace InterpretersOffice\Admin\Service;

use Swift_SmtpTransport;
use Swift_Message;
use Swift_Mailer;
use Swift_Transport;
use Swift_SpoolTransport;
use Swift_SendmailTransport;
use Swift_FileSpool;

/**
 * service for sending batch email
 *
 * A work in progress as we transition away from the inordinately
 * unwieldy Laminas\Mail
 */
class BatchEmailService
{
    /**
     * configuration
     *
     * @var array
     */
    private $config;



    /**
     * constructor
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * gets transport
     *
     * the nasty thing here is that the config is designed
     * to accomodate Laminas\Mail only
     *
     * @return Swift_Transport
     */
    public function getTransport() : Swift_Transport
    {
        $config = $this->config;
        if (stristr($config['transport'], 'sendmail')) {
            $transport = new Swift_SendmailTransport();
        } elseif (stristr($config['transport'], 'smtp')) {
            $options = $config['transport_options']['options'];
            $transport = new Swift_SmtpTransport(
                $options['host'],
                $options['port'] ?? 25,
                'ssl'
            );
            if (isset($options['connection_config'])) {
                $transport->setUserName($options['connection_config']['username'])
                ->setPassword($options['connection_config']['password']);
            }
        } elseif (stristr($config['transport'], 'file')) {
            $path = $config['transport_options']['options']['path'];
            $transport = new Swift_SpoolTransport(new Swift_FileSpool($path));
        } else {
            throw new \RuntimeException("can't figure out how to configure email transport");
        }

        return $transport;
    }
/*
   $config = $this->config['transport_options']['options'];
        $transport = new Swift_SmtpTransport(
            $config['host'],
            $config['port'],
            'ssl'
        );
        $transport->setUserName($config['connection_config']['username'])
            ->setPassword($config['connection_config']['password']);

        return $transport;

 */
    /**
     * sends test email
     *
     * @param string $address
     * @return int
     */
    public function test(string $address = null) : int
    {
        $config = $this->config['transport_options']['options'];
        $transport = new Swift_SmtpTransport(
            $config['host'],
            $config['port'],
            'ssl'
        );
        $transport->setUserName($config['connection_config']['username'])
            ->setPassword($config['connection_config']['password']);

        $message = new Swift_Message("test one two Swift_Mailer");
        $message->setBody('My <em>amazing</em> body of a test message sent via Swift_Mailer', 'text/html');

        // Add alternative parts with addPart()
        $message->addPart('My amazing body of a test message sent via Swift_Mailer, in plain text', 'text/plain');
        $message->setFrom($this->config['from_address'])
            ->setTo($address ?? 'david@davidmintz.org');
        $mailer = new Swift_Mailer($transport);

        $result = $mailer->send($message);

        return $result;
    }
}
