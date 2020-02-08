<?php
namespace Df\Cron\Model;
use Magento\Framework\Logger\Handler\Base as _P;
/**
 * 2020-02-08
 * @final Unable to use the PHP «final» keyword here because of the M2 code generation.
 * "The https://github.com/royalwholesalecandy/core/issues/57 solution works with Magento 2.2.5,
 * but does not work with Magento 2.3.2.":
 * https://github.com/tradefurniturecompany/core/issues/25#issuecomment-58373497
 */
class LoggerHandler extends _P {
	/**
	 * 2019-10-13
	 * @override
	 * @see \Monolog\Handler\AbstractProcessingHandler::handle()
	 * @param array(string => mixed) $d
	 * @return bool
	 */
	function handle(array $d) {return self::p($d) || parent::handle($d);}

	/**
	 * 2020-02-08
	 * @used-by handle()
	 * @used-by \Df\Framework\Logger\Handler\System::handle()
	 * @param array(string => mixed) $d
	 * @return bool
	 */
	static function p(array $d) {
		$m = dfa($d, 'message'); /** @var string $m */
		return
			/**
			 * 2019-12-24
			 * "Prevent the `Mirasvit\ReportApi` module from logging successful cron events":
			 * https://github.com/royalwholesalecandy/core/issues/59
			 * @see \Mirasvit\ReportApi\Processor\RequestProcessor::process()
			 *		$this->logger->info('ReportApi', [
			 *			'time' => microtime(true) - $timeStart,
			 *			'request' => $request->toArray(),
			 *		]);
			 */
			df_starts_with($m, 'ReportApi')
			/**
			 * 2019-10-13
			 * "Disable the logging of «Add of item with id %s was processed» messages to `system.log`":
			 * https://github.com/kingpalm-com/core/issues/36
			 */
			|| df_starts_with($m, 'Add of item with id') && df_ends_with($m, 'was processed')
			|| df_starts_with($m, 'Item') && df_ends_with($m, 'was removed')
			/**
			 * 2019-12-24
			 * "Prevent Magento from logging successful cron events":
			 * https://github.com/royalwholesalecandy/core/issues/57
			 * @see \Magento\Cron\Observer\ProcessCronQueueObserver::cleanupJobs()
			 * 		$this->logger->info(sprintf('%d cron jobs were cleaned', $count));
			 * https://github.com/magento/magento2/blob/2.3.3/app/code/Magento/Cron/Observer/ProcessCronQueueObserver.php#L549
			 */
			|| df_ends_with($m, 'cron jobs were cleaned')
			|| df_starts_with($m, 'Cron Job ') && (
				/**
				 * 2019-12-24
				 * "Prevent Magento from logging successful cron events":
				 * https://github.com/royalwholesalecandy/core/issues/57
				 * @see \Magento\Cron\Observer\ProcessCronQueueObserver::_runJob()
				 * 		$this->logger->info(sprintf('Cron Job %s is run', $jobCode));
				 * https://github.com/magento/magento2/blob/2.3.3/app/code/Magento/Cron/Observer/ProcessCronQueueObserver.php#L316
				 */
				df_ends_with($m, ' is run')
				/**
				 * 2019-12-24
				 * "Prevent Magento from logging successful cron events":
				 * https://github.com/royalwholesalecandy/core/issues/57
				 * @see \Magento\Cron\Observer\ProcessCronQueueObserver::_runJob()
				 *	$this->logger->info(sprintf(
				 *		'Cron Job %s is successfully finished. Statistics: %s',
				 *		$jobCode,
				 *		$this->getProfilingStat()
				 *	));
				 * https://github.com/magento/magento2/blob/2.3.3/app/code/Magento/Cron/Observer/ProcessCronQueueObserver.php#L352
				 */
				|| df_contains($m, ' is successfully finished. Statistics: ')
				/**
				 * 2019-12-24
				 * "Prevent Magento from logging successful cron events":
				 * https://github.com/royalwholesalecandy/core/issues/57
				 * @see \Magento\Cron\Observer\ProcessCronQueueObserver::_runJob()
				 *		if ($scheduledTime < $currentTime - $scheduleLifetime) {
				 *			$schedule->setStatus(Schedule::STATUS_MISSED);
				 *			throw new \Exception(sprintf(
				 * 				'Cron Job %s is missed at %s', $jobCode, $schedule->getScheduledAt()
				 * 			));
				 *		}
				 * https://github.com/magento/magento2/blob/2.3.3/app/code/Magento/Cron/Observer/ProcessCronQueueObserver.php#L291-L295
				 */
				|| df_my_local() && df_contains($m, ' is missed at ')
			)
			/**
			 * 2019-10-13
			 * I return `true` to prevent bubling to other loggers:
			 * @see \Monolog\Logger::addRecord():
			 *		while ($handler = current($this->handlers)) {
			 *			if (true === $handler->handle($record)) {
			 *				break;
			 *			}
			 *			next($this->handlers);
			 *		}
			 */
		;
	}
}