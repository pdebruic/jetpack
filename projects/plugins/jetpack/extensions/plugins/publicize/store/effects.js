/**
 * External dependencies
 */
import { flatMap, throttle } from 'lodash';
import apiFetch from '@wordpress/api-fetch';
import { serialize } from '@wordpress/blocks';
import { select, dispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { SUPPORTED_CONTAINER_BLOCKS } from '../components/twitter';

/**
 * Effect handler which will refresh the connection test results.
 *
 * @returns {object} Refresh connection test results action.
 */
export async function refreshConnectionTestResults() {
	try {
		const results = await apiFetch( { path: '/wpcom/v2/publicize/connection-test-results' } );

		// Combine current connections with new connections.
		const prevConnections = select( 'jetpack/publicize' ).getConnections();
		const prevConnectionIds = prevConnections.map( connection => connection.id );
		const freshConnections = results;
		const connections = [];

		/*
		 * Iterate connection by connection,
		 * in order to refresh or update current connections.
		 */
		for ( const freshConnection of freshConnections ) {
			let connection;
			if ( prevConnectionIds.includes( freshConnection.id ) ) {
				/*
				 * The connection is already defined.
				 * Do not overwrite the existing connection.
				 */
				connection = prevConnections.filter(
					prevConnection => prevConnection.id === freshConnection.id
				)[ 0 ];
			} else {
				/*
				 * Here the connection is new.
				 * Let's map it.
				 */
				connection = {
					display_name: freshConnection.display_name,
					service_name: freshConnection.service_name,
					id: freshConnection.id,
					done: false,
					enabled: true,
					toggleable: true,
				};
			}

			// Populate the connection with extra fresh data.
			if ( freshConnection.profile_picture ) {
				connection.profile_picture = freshConnection.profile_picture;
			}

			connections.push( connection );
		}

		// Update post metadata.
		return dispatch( editorStore ).editPost( { jetpack_publicize_connections: connections } );
	} catch ( error ) {
		// Refreshing connections failed
	}
}

/**
 * Effect handler which will update the connections
 * in the post metadata.
 *
 * @param {object} action              - Action which had initiated the effect handler.
 * @param {string} action.connectionId - Connection ID to switch.
 * @returns {object} Switch connection enable-status action.
 */
export async function toggleConnectionById( { connectionId } ) {
	const connections = select( 'jetpack/publicize' ).getConnections();

	/*
	 * Map connections re-defining the enabled state of the connection,
	 * based on the connection ID.
	 */
	const updatedConnections = connections.map( connection => ( {
		...connection,
		enabled: connection.id === connectionId ? ! connection.enabled : connection.enabled,
	} ) );

	// Update post metadata.
	return dispatch( editorStore ).editPost( { jetpack_publicize_connections: updatedConnections } );
}

/**
 * Given an array of blocks, this will return an array of just the blocks (including child blocks of
 * those blocks passed) that we support transforming into tweet content.
 *
 * @param {Array} blocks - The array of blocks to check.
 * @returns {Array} The blocks that can be turned into tweets.
 */
export const computeTweetBlocks = ( blocks = [] ) => {
	const { getSupportedBlockType } = select( 'jetpack/publicize' );
	return flatMap( blocks, ( block = {} ) => {
		if ( getSupportedBlockType( block.name ) ) {
			return block;
		}

		// core-embed/* blocks are a special case.
		if ( block.name.startsWith( 'core-embed/' ) ) {
			return block;
		}

		if ( SUPPORTED_CONTAINER_BLOCKS.includes( block.name ) ) {
			return computeTweetBlocks( block.innerBlocks );
		}

		return [];
	} );
};

/**
 * Handle sending the tweet refresh request.
 *
 * @returns {object} Refresh tweets results action.
 */
async function __refreshTweets() {
	const topBlocks = select( 'core/editor' ).getBlocks();

	const tweetBlocks = computeTweetBlocks( topBlocks );

	try {
		const results = await apiFetch( {
			path: '/wpcom/v2/tweetstorm/parse',
			data: {
				blocks: tweetBlocks.map( block => ( {
					attributes: block.attributes,
					block: serialize( block ),
					clientId: block.clientId,
				} ) ),
			},
			method: 'POST',
		} );

		// Start generating any missing Twitter cards.
		const urls = flatMap( results, block => block.urls );
		dispatch( 'jetpack/publicize' ).getTwitterCards( urls );

		return dispatch( 'jetpack/publicize' ).setTweets( results );
	} catch ( error ) {
		// Refreshing tweets failed
	}
}

/**
 * Effect handler which will refresh the state of the tweets. Tweet refreshes are throttled
 * to once ever 2 seconds.
 *
 * @param {object} action - Action which had initiated the effect handler.
 * @returns {object} Refresh tweets results action.
 */
export const refreshTweets = throttle( __refreshTweets, 2000, { leading: true, trailing: true } );

export async function getTwitterCards( action ) {
	if ( 0 === action.urls.length ) {
		return dispatch( 'jetpack/publicize' ).setTwitterCards( [] );
	}

	try {
		const results = await apiFetch( {
			path: '/wpcom/v2/tweetstorm/generate-cards',
			data: {
				urls: action.urls,
			},
			method: 'POST',
		} );
		return dispatch( 'jetpack/publicize' ).setTwitterCards( results );
	} catch ( error ) {
		// Refreshing tweets failed
	}
}

export default {
	REFRESH_CONNECTION_TEST_RESULTS: refreshConnectionTestResults,
	TOGGLE_CONNECTION_BY_ID: toggleConnectionById,
	REFRESH_TWEETS: refreshTweets,
	GET_TWITTER_CARDS: getTwitterCards,
};
