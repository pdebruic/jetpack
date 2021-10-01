/**
 * External dependencies
 */
import React, { useState, useCallback } from 'react';
import PropTypes from 'prop-types';
import { Button, Dashicon } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import restApi from '@automattic/jetpack-api';
import { Spinner } from '@automattic/jetpack-components';

/**
 * Internal dependencies
 */
import extractHostname from '../../tools/extract-hostname';

/**
 * The "migrate" card.
 *
 * @param {object} props - The properties.
 * @param {string} props.wpcomHomeUrl - The original site URL.
 * @param {string} props.currentUrl - The current site URL.
 * @param {string} props.redirectUri - The redirect URI to redirect users back to after connecting.
 * @returns {React.Component} The `ConnectScreen` component.
 */
const CardFresh = props => {
	const wpcomHostName = extractHostname( props.wpcomHomeUrl );
	const currentHostName = extractHostname( props.currentUrl );
	const redirectUri = props.redirectUri;

	const buttonLabel = __( 'Create a fresh connection', 'jetpack' );

	const [ isInProgress, setIsInProgress ] = useState( false );

	/**
	 * Initiate the migration.
	 * Placeholder for now.
	 *
	 * @todo Add the actual migration functionality.
	 */
	const doStartFresh = useCallback( () => {
		if ( ! isInProgress ) {
			setIsInProgress( true );

			restApi
				.startIDCFresh( redirectUri )
				.then( connectUrl => {
					window.location.href = connectUrl + '&from=idc-notice';
				} )
				.catch( error => {
					setIsInProgress( false );
					throw error;
				} );
		}
	}, [ isInProgress, setIsInProgress, redirectUri ] );

	return (
		<div className="jp-idc-card-action-base">
			<div className="jp-idc-card-action-top">
				<h4>{ __( 'Treat each site as independent sites', 'jetpack' ) }</h4>

				<p>
					{ createInterpolateElement(
						sprintf(
							/* translators: %1$s: The current site domain name. %2$s: The original site domain name. */
							__(
								'<hostname>%1$s</hostname> settings, stats, and subscribers will start fresh. <hostname>%2$s</hostname> will keep its data as is.',
								'jetpack'
							),
							currentHostName,
							wpcomHostName
						),
						{
							hostname: <strong />,
						}
					) }
				</p>
			</div>

			<div className="jp-idc-card-action-bottom">
				<div className="jp-idc-card-action-sitename">{ wpcomHostName }</div>
				<Dashicon icon="minus" className="jp-idc-card-action-separator" />
				<div className="jp-idc-card-action-sitename">{ currentHostName }</div>

				<Button
					className="jp-idc-card-action-button"
					label={ buttonLabel }
					onClick={ doStartFresh }
				>
					{ isInProgress ? <Spinner /> : buttonLabel }
				</Button>
			</div>
		</div>
	);
};

CardFresh.propTypes = {
	wpcomHomeUrl: PropTypes.string.isRequired,
	currentUrl: PropTypes.string.isRequired,
	redirectUri: PropTypes.string.isRequired,
};

export default CardFresh;
