import { test } from '@playwright/test';
import { prerequisitesBuilder } from '_jetpack-e2e-commons/env/prerequisites.js';
import { Sidebar, DashboardPage } from '_jetpack-e2e-commons/pages/wp-admin/index.js';
import playwrightConfig from '../playwright.config.mjs';

test.describe( 'Classic Theme Helper plugin!', () => {
	test.beforeEach( async ( { browser } ) => {
		const page = await browser.newPage( playwrightConfig.use );
		await prerequisitesBuilder( page ).withCleanEnv().withLoggedIn( true ).build();
		await page.close();
	} );

	// eslint-disable-next-line playwright/expect-expect -- TODO: Fix/justify this.
	test( 'Visit Jetpack page', async ( { page } ) => {
		await DashboardPage.visit( page );
		await ( await Sidebar.init( page ) ).selectJetpack();
	} );
} );
