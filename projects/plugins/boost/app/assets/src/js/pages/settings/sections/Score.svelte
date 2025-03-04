<script>
	import ComputerIcon from '../../../svg/computer.svg';
	import MobileIcon from '../../../svg/mobile.svg';
	import RefreshIcon from '../../../svg/refresh.svg';
	import ScoreBar from '../elements/ScoreBar.svelte';
	import ScoreContext from '../elements/ScoreContext.svelte';
	import ErrorNotice from '../../../elements/ErrorNotice.svelte';
	import {
		getScoreLetter,
		requestSpeedScores,
		didScoresImprove,
		getScoreImprovementPercentage,
	} from '../../../api/speed-scores';
	import debounce from '../../../utils/debounce';
	import { __ } from '@wordpress/i18n';
	import { criticalCssStatus } from '../../../stores/critical-css-status';
	import { modules } from '../../../stores/modules';
	import { derived } from 'svelte/store';
	import RatingCard from '../elements/RatingCard.svelte';

	const siteIsOnline = Jetpack_Boost.site.online;

	let loadError;
	let isLoading = siteIsOnline;
	let showPrevScores;
	let scoreLetter = '';
	let scores = {
		current: {
			mobile: 0,
			desktop: 0,
		},
		noBoost: null,
	};
	let showRatingCard = false;
	let improvementPercentage = 0;

	refreshScore( false );

	/**
	 * Derived datastore which makes it easy to check if module states are currently in sync with server.
	 */
	const modulesInSync = derived( modules, $modules => {
		return ! Object.values( $modules ).some( m => m.synced === false );
	} );

	/**
	 * String representation of the current state that may impact the score.
	 *
	 * @type {Readable<string>}
	 */
	const scoreConfigString = derived(
		[ modules, criticalCssStatus ],
		( [ $modules, $criticalCssStatus ] ) =>
			JSON.stringify( {
				modules: $modules,
				criticalCss: {
					created: $criticalCssStatus.created,
				},
			} )
	);

	/**
	 * The configuration that led to latest speed score.
	 *
	 * @type {Readable<string>}
	 */
	let currentScoreConfigString = $scoreConfigString;

	async function refreshScore( force = false ) {
		if ( ! siteIsOnline ) {
			return;
		}
		
		isLoading = true;
		loadError = undefined;

		try {
			scores = await requestSpeedScores( force );
			scoreLetter = getScoreLetter( scores.current.mobile, scores.current.desktop );
			showPrevScores = didScoresImprove( scores );
			currentScoreConfigString = $scoreConfigString;
		} catch ( err ) {
			console.log( err );
			loadError = err;
		} finally {
			isLoading = false;
		}
	}

	/**
	 * A store that checks the speed score needs a refresh.
	 */
	const needRefresh = derived(
		[ criticalCssStatus, modulesInSync, scoreConfigString ],
		( [ $criticalCssStatus, $modulesInSync, $scoreConfigString ] ) => {
			return (
				! $criticalCssStatus.generating &&
				$modulesInSync &&
				$scoreConfigString !== currentScoreConfigString
			);
		}
	);

	const debouncedRefreshScore = debounce( force => {
		if ( $needRefresh ) {
			refreshScore( force );
		}
	}, 2000 );

	$: if ( $needRefresh ) {
		debouncedRefreshScore( true );
	}

	$: {
		if ( didScoresImprove( scores ) && Jetpack_Boost.preferences.showRatingPrompt ) {
			showRatingCard = true;
			Jetpack_Boost.preferences.showRatingPrompt = false;
			improvementPercentage = getScoreImprovementPercentage( scores );
		}
	}
</script>

<div class="jb-container">
	<div class="jb-site-score" class:loading={isLoading}>
		{#if siteIsOnline}
			<div class="jb-site-score__top">
				<h2>
					{#if isLoading}
						{__( 'Loading…', 'jetpack-boost' )}
					{:else if loadError}
						{__( 'Whoops, something went wrong', 'jetpack-boost' )}
					{:else}
						{__( 'Overall score', 'jetpack-boost' )}: {scoreLetter}
					{/if}
				</h2>
				{#if ! isLoading && ! loadError}
					<ScoreContext />
				{/if}
				<button
					type="button"
					class="components-button is-link"
					disabled={isLoading}
					on:click={() => refreshScore( true )}
				>
					<RefreshIcon />
					{__( 'Refresh', 'jetpack-boost' )}
				</button>
			</div>
		{:else}
			<div class="jb-site-score__offline">
				<h2>
					{__( 'Website Offline', 'jetpack-boost' )}
				</h2>
				<p>
					{__(
						'All Jetpack Boost features are still available, but to get a performance score you would first have to make your website available online.',
						'jetpack-boost'
					)}
				</p>
			</div>
		{/if}

		{#if loadError}
			<ErrorNotice
				title={__( 'Failed to load Speed Scores', 'jetpack-boost' )}
				error={loadError}
				suggestion={__( '<action name="retry">Try again</action>', 'jetpack-boost' )}
				on:retry={() => refreshScore( true )}
			/>
		{/if}

		<div class="jb-score-bar jb-score-bar--mobile">
			<div class="jb-score-bar__label">
				<MobileIcon />
				<div>{__( 'Mobile score', 'jetpack-boost' )}</div>
			</div>
			<ScoreBar
				prevScore={scores.noBoost?.mobile}
				score={scores.current.mobile}
				active={siteIsOnline}
				{isLoading}
				{showPrevScores}
				noBoostScoreTooltip={__( 'Your mobile score without Boost', 'jetpack-boost' )}
			/>
		</div>

		<div class="jb-score-bar jb-score-bar--desktop">
			<div class="jb-score-bar__label">
				<ComputerIcon />
				<div>{__( 'Desktop score', 'jetpack-boost' )}</div>
			</div>
			<ScoreBar
				prevScore={scores.noBoost?.desktop}
				score={scores.current.desktop}
				active={siteIsOnline}
				{isLoading}
				{showPrevScores}
				noBoostScoreTooltip={__( 'Your desktop score without Boost', 'jetpack-boost' )}
			/>
		</div>
	</div>
</div>
{#if showRatingCard}
	<RatingCard on:dismiss={() => ( showRatingCard = false )} improvement={improvementPercentage} />
{/if}
