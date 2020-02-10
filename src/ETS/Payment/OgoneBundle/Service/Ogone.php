<?php

namespace ETS\Payment\OgoneBundle\Service;

use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;
use JMS\Payment\CoreBundle\Plugin\GatewayPlugin;
use JMS\Payment\CoreBundle\PluginController\PluginControllerInterface;

use ETS\Payment\OgoneBundle\Exception\NoPendingTransactionException;
use ETS\Payment\OgoneBundle\Hash\GeneratorInterface;
use ETS\Payment\OgoneBundle\Response\FeedbackResponse;

class Ogone
{
    /**
     * @var JMS\Payment\CoreBundle\Plugin\PluginInterface
     */
    protected $pluginController;

    /**
     * @var JMS\Payment\CoreBundle\Plugin\GatewayPlugin
     */
    protected $ogonePlugin;

    /**
     * @var ETS\Payment\OgoneBundle\Hash\GeneratorInterface
     */
    protected $generator;

    /**
     * @var ETS\Payment\OgoneBundle\Response\FeedbackResponse
     */
    protected $feedbackResponse;

    /**
     * @param PluginControllerInterface $pluginController
     * @param GatewayPlugin             $ogonePlugin
     * @param GeneratorInterface        $generator
     * @param FeedbackResponse          $feedbackResponse
     */
    public function __construct(PluginControllerInterface $pluginController, GatewayPlugin $ogonePlugin, GeneratorInterface $generator, FeedbackResponse $feedbackResponse)
    {
        $this->pluginController = $pluginController;
        $this->ogonePlugin      = $ogonePlugin;
        $this->generator        = $generator;
        $this->feedbackResponse = $feedbackResponse;
    }

    /**
     * Triggers the approveAndDeposit method of the plugin controller after
     * checking if the feedback response has a valid hash and that the payment instruction has pending transactions.
     *
     * @param PaymentInstructionInterface $instruction
     *
     * @throws \LogicException               If hash is not valid or if there is no pending transaction
     * @throws NoPendingTransactionException If no pending transaction is found in payment instruction
     */
    public function handleTransactionFeedback(PaymentInstructionInterface $instruction)
    {
        if (!$this->isHashValid($this->feedbackResponse->getValues(), $this->feedbackResponse->getHash())) {
            throw new \LogicException(sprintf('[Ogone - callback] hash verification failed with values [%s] and hash [%s]',
                print_r($this->feedbackResponse->getValues(), true),
                $this->feedbackResponse->getHash()
            ));
        }

        if (null === $pendingTransaction = $instruction->getPendingTransaction()) {
            throw new NoPendingTransactionException(sprintf('[Ogone - callback] no pending transaction found for the payment instruction [%d]', $instruction->getId()));
        }

        foreach ($this->feedbackResponse->getValues() as $field => $value) {
            $pendingTransaction->getExtendedData()->set($field, $value);
        }

        $this->ogonePlugin->setFeedbackResponse($this->feedbackResponse);

        $pendingTransaction->setReferenceNumber($this->feedbackResponse->getPaymentId());

        $this->pluginController->approveAndDeposit($pendingTransaction->getPayment()->getId(), $this->feedbackResponse->getAmount());
    }

    /**
     * compares the hash given by ogone with a hash generated by Sha1Out generator
     *
     * @param  array   $values the order values sent by Ogone
     * @param  string  $hash   the hash provided by Ogone with the order values
     *
     * @return boolean         true if the hashes match
     */
    protected function isHashValid(array $values, $hash)
    {
        return $this->generator->generate($values) === $hash;
    }

    public function isValidFeedback() {
        if (!$this->isHashValid($this->feedbackResponse->getValues(), $this->feedbackResponse->getHash())) {
            return false;
        } else {
            return true;
        }
    }
}
