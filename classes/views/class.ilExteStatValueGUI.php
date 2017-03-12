<?php

/**
 * GUI for showing statistical values
 */
class ilExteStatValueGUI
{
	/**
	 * @var ilExtendedTestStatisticsPlugin
	 */
	protected $plugin;

	/**
	 * @var bool	comments should be shown as tooltip
	 */
	protected $show_comment = true;

	/**
	 * ilExteStatValueGUI constructor.
	 * @param ilExtendedTestStatisticsPlugin	$a_plugin
	 */
	public function __construct($a_plugin)
	{
		$this->plugin = $a_plugin;
		$this->plugin->includeClass('models/class.ilExteStatValue.php');
	}

	/**
	 * Set whether comments should be shown
	 * @param bool $a_show_comment
	 */
	public function setShowComment($a_show_comment)
	{
		$this->show_comment = $a_show_comment;
	}

	/**
	 * Get the rendered HTML for a value
	 * @param ilExteStatValue $value
	 * @return string
	 */
	public function getHTML(ilExteStatValue $value)
	{
		global $lng;

		$template = $this->plugin->getTemplate('tpl.il_exte_stat_value.html');

		// alert
		if ($value->alert != ilExteStatValue::ALERT_NONE)
		{
			$template->setVariable('SRC_ALERT', $this->plugin->getImagePath('alert_'.$value->alert.'.svg'));
			if (isset($value->value) and $value->type == ilExteStatValue::TYPE_ALERT)
			{
				$template->setVariable('ALT_ALERT', ilUtil::prepareFormOutput($value->value));
			}
			else
			{
				$template->setVariable('ALT_ALERT', $this->plugin->txt('alert_'.$value->alert));
			}
		}

		// value
		if (isset($value->value))
		{
			$template->setCurrentBlock($value->uncertain ? 'uncertain_value' : 'value');
			switch ($value->type)
			{
				case ilExteStatValue::TYPE_ALERT:
					// alert is already set
					break;

				case ilExteStatValue::TYPE_TEXT:
					$template->setVariable('VALUE', $this->textDisplay($value->value), true);
					break;

				case ilExteStatValue::TYPE_NUMBER:
					$template->setVariable('VALUE', round($value->value, $value->precision));
					break;

				case ilExteStatValue::TYPE_DURATION:
					$diff_seconds = $value->value;
					$diff_hours    = floor($diff_seconds/3600);
					$diff_seconds -= $diff_hours   * 3600;
					$diff_minutes  = floor($diff_seconds/60);
					$diff_seconds -= $diff_minutes * 60;
					$template->setVariable('VALUE', sprintf("%02d:%02d:%02d", $diff_hours, $diff_minutes, $diff_seconds));
					break;

				case ilExteStatValue::TYPE_DATETIME:
					if ($value->value instanceof ilDateTime)
					{
						$template->setVariable('VALUE', ilDatePresentation::formatDate($value->value));
					}
					break;
				case ilExteStatValue::TYPE_PERCENTAGE:
					$template->setVariable('VALUE', round($value->value, $value->precision). '%');
					break;

				case ilExteStatValue::TYPE_BOOLEAN:
					$template->setVariable('VALUE', $value->value ? $lng->txt('yes') : $lng->txt('no'));
					break;
			}
			$template->parseCurrentBlock();
		}

		// comment
		if ($this->show_comment && !empty($value->comment))
		{
			$comment_id = rand(100000,999999);
			require_once("Services/UIComponent/Tooltip/classes/class.ilTooltipGUI.php");
			ilTooltipGUI::addTooltip('ilExteStatComment'.$comment_id, $value->comment);
			$template->setVariable('COMMENT_ID', $comment_id);
		}

		return $template->get();
	}

	/**
	 * Get legend data
	 * @return array	[ ['value' => ilExteStatValue, 'description' => string], ...]
	 */
	public function getLegendData()
	{
		global $lng;

		$data = array (
			array(
				'value' => ilExteStatValue::_create('', ilExteStatValue::TYPE_ALERT, 0, '', ilExteStatValue::ALERT_GOOD),
				'description' => $this->plugin->txt('legend_alert_good')
			),
			array(
				'value' => ilExteStatValue::_create('', ilExteStatValue::TYPE_ALERT, 0, '', ilExteStatValue::ALERT_MEDIUM),
				'description' => $this->plugin->txt('legend_alert_medium')
			),
			array(
				'value' => ilExteStatValue::_create('', ilExteStatValue::TYPE_ALERT, 0, '', ilExteStatValue::ALERT_BAD),
				'description' => $this->plugin->txt('legend_alert_bad')
			),
			array(
				'value' => ilExteStatValue::_create('', ilExteStatValue::TYPE_ALERT, 0, '', ilExteStatValue::ALERT_UNKNOWN),
				'description' => $this->plugin->txt('legend_alert_unknown')
			),
			array(
				'value' => ilExteStatValue::_create($lng->txt('value'), ilExteStatValue::TYPE_TEXT, 0, '', ilExteStatValue::ALERT_NONE, true),
				'description' => $this->plugin->txt('legend_uncertain')
			),
			array(
				'value' => ilExteStatValue::_create($lng->txt('value'), ilExteStatValue::TYPE_TEXT, 0, $lng->txt('comment')),
				'description' => $this->plugin->txt('legend_comment')
			)
		);

		return $data;
	}


	/**
	 * Prepare a string value to be displayed in HTML
	 * @param $text
	 * @return mixed|string
	 */
	protected function textDisplay($text)
	{
		// these would be deleted by the template engine
		$text = str_replace('{','&#123;', $text);
		$text = str_replace('}','&#125;', $text);

		$text = preg_replace('/<span class="latex">(.*)<\/span>/','[tex]$1[/tex]', $text);
		$text = ilUtil::secureString($text, false);
		$text = ilUtil::insertLatexImages($text);

		return $text;
	}
}