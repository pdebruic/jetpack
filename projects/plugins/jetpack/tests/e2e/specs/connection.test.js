import {
	doSiteLevelConnection,
	doClassicConnection,
} from 'jetpack-e2e-commons/flows/jetpack-connect';
import Sidebar from 'jetpack-e2e-commons/pages/wp-admin/sidebar';
import JetpackPage from 'jetpack-e2e-commons/pages/wp-admin/jetpack';
import DashboardPage from 'jetpack-e2e-commons/pages/wp-admin/dashboard';
import { testStep } from 'jetpack-e2e-commons/reporters/reporter';
import { prerequisitesBuilder } from 'jetpack-e2e-commons/env/prerequisites';

/**
 *
 * @group connection
 */
describe( 'Connection', () => {
	beforeEach( async () => {
		await prerequisitesBuilder()
			.withLoggedIn( true )
			.withWpComLoggedIn( true )
			.withConnection( false )
			.build();
		await DashboardPage.visit( page );
		await ( await Sidebar.init( page ) ).selectJetpack();
	} );

	afterEach( async () => {
		await prerequisitesBuilder().withCleanEnv().build();
	} );

	it( 'User-less', async () => {
		await testStep( 'Can clean up WPCOM cookie', async () => {
			await ( await Sidebar.init( page ) ).removeCookieByName( 'wordpress_logged_in' );
		} );

		await testStep( 'Can start Site Level connection', async () => {
			await doSiteLevelConnection();
		} );

		await testStep( 'Can assert that site is connected', async () => {
			const jetpackPage = await JetpackPage.init( page );
			expect( await jetpackPage.isConnected() ).toBeTruthy();
		} );
	} );

	it( 'Classic', async () => {
		await testStep( 'Can start classic connection', async () => {
			await doClassicConnection( true );
		} );

		await testStep( 'Can assert that site is connected', async () => {
			const jetpackPage = await JetpackPage.init( page );
			expect( await jetpackPage.isConnected() ).toBeTruthy();
		} );
	} );
} );
