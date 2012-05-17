<?php
namespace Blocks;

/**
 *
 */
class VerifyPasswordForm extends \CFormModel
{
	public $password;
	public $confirmPassword;

/**
 * @return array
 */
	public function rules()
	{
		return array(
			array('password, confirmPassword', 'required'),
			array('password', 'compare', 'compareAttribute' => 'confirmPassword'),
			array('password', 'length', 'min' => b()->security->getMinimumPasswordLength())
		);
	}
}
