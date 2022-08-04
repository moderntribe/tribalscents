<?php
namespace TribalScents\Sniffs\Whitespace;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Enforces spacing around logical operators and assignments, based upon Squiz code
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @author   John Godley <john@urbangiraffe.com>
 * @author   Greg Sherwood <gsherwood@squiz.net>
 * @author   Marc McIntyre <mmcintyre@squiz.net>
 */

/**
 * TribalScents_Sniffs_WhiteSpace_ControlStructureSpacingSniff.
 *
 * Checks that any array declarations are lower case.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @author   John Godley <john@urbangiraffe.com>
 * @author   Greg Sherwood <gsherwood@squiz.net>
 * @author   Marc McIntyre <mmcintyre@squiz.net>
 */
class ControlStructureSpacingSniff implements Sniff
{
	/**
	 * A list of tokenizers this sniff supports.
	 *
	 * @var array
	 */
	public $supportedTokenizers = array( 'PHP' );

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register()
	{
		return array(
			T_IF,
			T_WHILE,
			T_FOREACH,
			T_FOR,
			T_SWITCH,
			T_DO,
			T_ELSE,
			T_ELSEIF,
		);
	}//end register

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param int  $stackPtr  The position of the current token in the
	 *                        stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr )
	{
		$tokens = $phpcsFile->getTokens();

		if ( isset( $tokens[ $stackPtr ]['scope_closer'] ) === false )
		{
			return;
		}

		$scopeCloser = $tokens[ $stackPtr ]['scope_closer'];
		$scopeOpener = $tokens[ $stackPtr ]['scope_opener'];

		$openBracket = $phpcsFile->findNext( Tokens::$emptyTokens, ( $stackPtr + 1 ), null, true );

		if ( $tokens[ ( $openBracket + 1 ) ]['code'] !== T_WHITESPACE && $tokens[ ( $openBracket + 1 ) ]['code'] !== T_CLOSE_PARENTHESIS )
		{
			// Checking this: $value = my_function([*]...).
			$error = 'There must be a space after the opening parenthesis.';
			$phpcsFile->addError( $error, $stackPtr, 'invalidWhitespace' );
		}

		if ( isset( $tokens[ $openBracket ]['parenthesis_closer'] ) === true )
		{
			$closer = $tokens[ $openBracket ]['parenthesis_closer'];

			if (
				$tokens[ ( $closer - 1 ) ]['code'] !== T_WHITESPACE
				|| $tokens[ ( $closer + 1 ) ]['code'] !== T_WHITESPACE
			)
			{
				$error = 'There must be a space before and after the closing parenthesis.';
				$phpcsFile->addError( $error, $closer, 'invalidWhitespace' );
			}
		}//end if

		$trailingContent = $phpcsFile->findNext( T_WHITESPACE, ( $scopeCloser + 1 ), null, true );
		if ( $tokens[ $trailingContent ]['code'] === T_ELSE )
		{
			if ( $tokens[ $stackPtr ]['code'] === T_IF )
			{
				// IF with ELSE.
				return;
			}//end if
		}//end if

		if ( $tokens[ $trailingContent ]['code'] === T_COMMENT )
		{
			if ( $tokens[ $trailingContent ]['line'] === $tokens[ $scopeCloser ]['line'] )
			{
				if ( substr( $tokens[ $trailingContent ]['content'], 0, 5 ) === '//end' )
				{
					// There is an end comment, so we have to get the next piece
					// of content.
					$trailingContent = $phpcsFile->findNext( T_WHITESPACE, ( $trailingContent + 1 ), null, true );
				}//end if
			}//end if
		}//end if

		if ( $tokens[ $trailingContent ]['code'] === T_BREAK )
		{
			// If this BREAK is closing a CASE, we don't need the
			// blank line after this control structure.
			if ( isset( $tokens[ $trailingContent ]['scope_condition'] ) === true )
			{
				$condition = $tokens[ $trailingContent ]['scope_condition'];
				if ( $tokens[ $condition ]['code'] === T_CASE || $tokens[ $condition ]['code'] === T_DEFAULT )
				{
					return;
				}//end if
			}//end if
		}//end if

		if ( $tokens[ $trailingContent ]['code'] === T_CLOSE_TAG )
		{
			// At the end of the script or embedded code.
			return;
		}//end if

		if ( $tokens[ $trailingContent ]['code'] === T_CLOSE_CURLY_BRACKET )
		{
			// Another control structure's closing brace.
			if ( isset( $tokens[ $trailingContent ]['scope_condition'] ) === true )
			{
				$owner = $tokens[ $trailingContent ]['scope_condition'];
				if ( $tokens[ $owner ]['code'] === T_FUNCTION )
				{
					// The next content is the closing brace of a function
					// so normal function rules apply and we can ignore it.
					return;
				}//end if
			}//end if

			if ( $tokens[ $trailingContent ]['line'] !== ( $tokens[ $scopeCloser ]['line'] + 1 ) )
			{
				$error = 'Blank line found after control structure';
				$phpcsFile->addError( $error, $scopeCloser, 'invalidWhitespace' );
			}//end if
		}//end if
	}//end process
}//end class
