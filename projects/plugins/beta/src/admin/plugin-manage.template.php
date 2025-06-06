<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
/**
 * Jetpack Beta wp-admin manage page contents.
 *
 * @html-template \Automattic\JetpackBeta\Admin::render
 * @html-template-var \Automattic\JetpackBeta\Plugin $plugin Plugin being managed.
 * @package automattic/jetpack-beta
 */

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable -- HTML template, let Phan handle it.

use Automattic\JetpackBeta\Admin;
use Automattic\JetpackBeta\Utils;
use Composer\Semver\Semver;

// Check that the file is not accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

$manifest   = $plugin->get_manifest( true );
$wporg_data = $plugin->get_wporg_data( true );

$existing_branch = null;
if ( file_exists( $plugin->plugin_path() ) ) {
	$tmp             = get_plugin_data( $plugin->plugin_path(), false, false );
	$existing_branch = $plugin->source_info( 'release', $tmp['Version'] );
	if ( ! $existing_branch || is_wp_error( $existing_branch ) ) {
		$existing_branch = (object) array(
			'which'          => 'stable',
			'source'         => 'unknown',
			'id'             => $tmp['Version'],
			'version'        => $tmp['Version'],
			'pretty_version' => $plugin->stable_pretty_version(),
		);
	}
}

$active_branch = (object) array(
	'which'  => null,
	'source' => null,
	'id'     => null,
);
$version       = null;
$verslug       = '';
if ( $plugin->is_active( 'stable' ) ) {
	$active_branch = $existing_branch;
	$verslug       = $plugin->plugin_slug();
	$version       = $active_branch->pretty_version;
} elseif ( $plugin->is_active( 'dev' ) ) {
	$active_branch = $plugin->dev_info();
	if ( $active_branch ) {
		$active_branch->which          = 'dev';
		$active_branch->pretty_version = $plugin->dev_pretty_version();
	} else {
		$tmp           = get_plugin_data( $plugin->dev_plugin_path(), false, false );
		$active_branch = (object) array(
			'which'          => 'dev',
			'source'         => 'unknown',
			'id'             => $tmp['Version'],
			'version'        => $tmp['Version'],
			'pretty_version' => __( 'Unknown Development Version', 'jetpack-beta' ),
		);
	}
	$verslug = $plugin->dev_plugin_slug();
	$version = $active_branch->pretty_version . ' | ' . $active_branch->version;
}

?>
<?php require __DIR__ . '/header.template.php'; ?>
<div class="jetpack-beta-container" >
	<div id="jetpack-beta-tester__breadcrumb">
		<a href="<?php echo esc_url( Utils::admin_url() ); ?>">
			<?php esc_html_e( 'Jetpack Beta Tester Home', 'jetpack-beta' ); ?>
		</a>
		<span>&nbsp;&gt; <?php echo esc_html( $plugin->get_name() ); ?></span>
	</div>
	<?php
	if ( ! Utils::has_been_used() ) {
		require __DIR__ . '/notice.template.php';
	}
	?>
	<?php
	// While the toggles apply globally rather than per-plugin, it might be confusing to show them on mu-plugins where they don't have any effect.
	if ( ! $plugin->is_mu_plugin() ) {
		require __DIR__ . '/toggles.template.php';
	}
	?>
	<?php require __DIR__ . '/show-needed-updates.template.php'; ?>

	<?php
	if ( $plugin->is_mu_plugin() ) {
		$url = sprintf( 'https://github.com/Automattic/jetpack-beta/blob/%s/docs/mu-plugin-info.md', rawurlencode( str_ends_with( JPBETA_VERSION, '-alpha' ) ? 'HEAD' : JPBETA_VERSION ) );
		?>
		<div id="jetpack-beta-tester__is-mu-plugin" class="dops-card">
			<p><?php echo esc_html( $plugin->get_name() ); ?> will be installed as a mu-plugin. See <a href="<?php echo esc_url( $url ); ?>">the documentation</a> for details on what this entails, particularly if you're newly installing a stable version.</p>
		</div>
	<?php } ?>

	<?php if ( null !== $version ) { ?>
	<div class="dops-foldable-card is-expanded has-expanded-summary dops-card is-compact">
		<div class="dops-foldable-card__header has-border">
			<span class="dops-foldable-card__main">
				<span class="dops-foldable-card__header-text">
					<?php echo esc_html( $plugin->get_name() ); ?> - Currently Running
				</span>
			</span>
		</div>
		<div class="dops-foldable-card__content">
			<p data-jpbeta-version-for="<?php echo esc_attr( $verslug ); ?>"><?php echo wp_kses_post( $version ); ?></p>
		</div>
	</div>
	<div class="dops-foldable-card has-expanded-summary dops-card">
		<div class="dops-foldable-card__header has-border">
			<span class="dops-foldable-card__main">
				<div class="dops-foldable-card__header-text">
					<div class="dops-foldable-card__header-text"><?php esc_html_e( 'Found a bug?', 'jetpack-beta' ); ?></div>
				</div>
			</span>
			<span class="dops-foldable-card__secondary" >
				<span class="dops-foldable-card__summary">
					<a type="button" href="<?php echo esc_url( $plugin->bug_report_url() ); ?>"
						class="is-primary jp-form-button dops-button is-primary is-compact jptracks"
						data-jptracks-name="jetpack_beta_submit_report"
						data-jptracks-prop="<?php echo esc_attr( $plugin->plugin_slug() . ' ' . $active_branch->version ); ?>"
					>
						<?php esc_html_e( 'Report it!', 'jetpack-beta' ); ?>
					</a>
				</span>
			</span>
		</div>
	</div>
	<?php } ?>
	<div class="jetpack-beta__wrap">
		<?php
		if ( $existing_branch && 'unknown' === $existing_branch->source ) {
			$branch                 = clone $existing_branch;
			$branch->pretty_version = __( 'Existing Version', 'jetpack-beta' );
			require __DIR__ . '/branch-card.template.php';
		}
		if ( $plugin->is_mu_plugin() && $active_branch && $active_branch->which === 'dev' && ! $existing_branch ) {
			// This is a bit of a cheat. Telling it to activate an "unknown" existing stable version when there is no
			// existing stable version has the effect of deactivating the plugin. This saves us having to write a special handler
			// for mu-plugin deactivation.
			$branch = (object) array(
				'which'          => 'stable',
				'source'         => 'unknown',
				'id'             => 'deactivate', // Arbitrary, unused.
				'version'        => '',
				'pretty_version' => 'Deactivate mu-plugin',
			);
			require __DIR__ . '/branch-card.template.php';
		}
		?>
		<?php
		$branch = $plugin->source_info( 'stable', '' );
		if ( $branch && ! is_wp_error( $branch ) ) {
			$branch->pretty_version = __( 'Latest Stable', 'jetpack-beta' );
			require __DIR__ . '/branch-card.template.php';

			// Fixup `$active_branch` so it doesn't show up as "active" under releases below.
			if ( $active_branch->source === $branch->source && $active_branch->id === $branch->id ) {
				$active_branch->source = 'stable';
				$active_branch->id     = '';
			}
		}
		?>
		<?php
		$branch = $plugin->source_info( 'rc', '' );
		if ( $branch && ! is_wp_error( $branch ) ) {
			require __DIR__ . '/branch-card.template.php';
		}
		?>
		<?php
		$branch = $plugin->source_info( 'trunk', '' );
		if ( $branch && ! is_wp_error( $branch ) ) {
			require __DIR__ . '/branch-card.template.php';
		}
		?>

		<?php if ( empty( $manifest->pr ) || ! (array) $manifest->pr ) { ?>
		<div id="section-pr">
			<?php
			if ( 'pr' === $active_branch->source ) {
				$branch                 = clone $active_branch;
				$branch->pretty_version = $branch->branch;
				require __DIR__ . '/branch-card.template.php';
			}
			?>
		</div>
		<?php } else { ?>
		<div class="dops-navigation">
			<div class="dops-section-nav has-pinned-items">
				<div class="dops-section-nav__panel">
					<div class="is-pinned is-open dops-search" role="search">
						<div aria-controls="search-component" aria-label="<?php esc_attr_e( 'Open Search', 'jetpack-beta' ); ?>" tabindex="-1">
							<svg class="gridicon gridicons-search dops-search-open__icon" height="24"
								viewbox="0 0 24 24" width="24">
								<g>
									<path d="M21 19l-5.154-5.154C16.574 12.742 17 11.42 17 10c0-3.866-3.134-7-7-7s-7 3.134-7 7 3.134 7 7 7c1.42 0 2.742-.426 3.846-1.154L19 21l2-2zM5 10c0-2.757 2.243-5 5-5s5 2.243 5 5-2.243 5-5 5-5-2.243-5-5z"></path>
								</g>
							</svg>
						</div>
						<input aria-hidden="false" class="dops-search__input" id="search-component-prs"
							placeholder="<?php esc_attr_e( 'Search for a Feature Branch', 'jetpack-beta' ); ?>" role="search" type="search" value="">
						<span aria-controls="search-component" id="search-component-prs-close" aria-label="<?php esc_attr_e( 'Close Search', 'jetpack-beta' ); ?>" tabindex="0">
							<svg class="gridicon gridicons-cross dops-search-close__icon" height="24"
								viewbox="0 0 24 24" width="24">
								<g>
									<path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"></path>
								</g>
							</svg>
						</span>
					</div>
				</div>
			</div>
		</div>
		<div id="section-pr">
			<?php
			$pr_list = (array) $manifest->pr;
			end( $pr_list );
			$last = key( $pr_list );
			foreach ( $pr_list as $k => $pr ) {
				$branch = $plugin->source_info( 'pr', $pr->branch );
				if ( $branch && ! is_wp_error( $branch ) ) {
					// Add spaces around the branch name for historical reasons.
					$branch->pretty_version = strtr(
						$branch->branch,
						array(
							'/' => ' / ',
							'-' => ' ',
						)
					);
					$branch->is_last        = $k === $last;
					require __DIR__ . '/branch-card.template.php';
				}
			}
			?>
		</div>
		<?php } ?>

		<?php if ( empty( $wporg_data->versions ) || ! (array) $wporg_data->versions ) { ?>
		<div id="section-releases">
			<?php
			if ( 'release' === $active_branch->source && $wporg_data->version !== $active_branch->id ) {
				$branch = $active_branch;
				require __DIR__ . '/branch-card.template.php';
			}
			?>
		</div>
		<?php } else { ?>
		<div class="dops-navigation">
			<div class="dops-section-nav has-pinned-items">
				<div class="dops-section-nav__panel">
					<div class="is-pinned is-open dops-search" role="search">
						<div aria-controls="search-component" aria-label="<?php esc_attr_e( 'Open Search', 'jetpack-beta' ); ?>" tabindex="-1">
							<svg class="gridicon gridicons-search dops-search-open__icon" height="24"
								viewbox="0 0 24 24" width="24">
								<g>
									<path d="M21 19l-5.154-5.154C16.574 12.742 17 11.42 17 10c0-3.866-3.134-7-7-7s-7 3.134-7 7 3.134 7 7 7c1.42 0 2.742-.426 3.846-1.154L19 21l2-2zM5 10c0-2.757 2.243-5 5-5s5 2.243 5 5-2.243 5-5 5-5-2.243-5-5z"></path>
								</g>
							</svg>
						</div>
						<input aria-hidden="false" class="dops-search__input" id="search-component-releases"
							placeholder="<?php esc_attr_e( 'Search for a release', 'jetpack-beta' ); ?>" role="search" type="search" value="">
						<span aria-controls="search-component" id="search-component-releases-close" aria-label="<?php esc_attr_e( 'Close Search', 'jetpack-beta' ); ?>" tabindex="0">
							<svg class="gridicon gridicons-cross dops-search-close__icon" height="24"
								viewbox="0 0 24 24" width="24">
								<g>
									<path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"></path>
								</g>
							</svg>
						</span>
					</div>
				</div>
			</div>
		</div>
		<div id="section-releases">
			<?php
			$versions = array_keys( (array) $wporg_data->versions );
			$versions = Semver::rsort( $versions );
			end( $versions );
			$last = key( $versions );
			foreach ( $versions as $k => $v ) {
				$branch = $plugin->source_info( 'release', $v );
				if ( $branch && ! is_wp_error( $branch ) ) {
					unset( $branch->updated_date );
					$branch->pretty_version = $branch->version;
					$branch->is_last        = $k === $last;
					require __DIR__ . '/branch-card.template.php';
				}
			}
			?>
		</div>
		<?php } ?>
	</div>

	<?php
	list( $to_test, $what_changed ) = Admin::to_test_content( $plugin );
	if ( $to_test ) {
		?>
		<div class="dops-foldable-card is-expanded has-expanded-summary dops-card is-compact">
			<div class="dops-foldable-card__header has-border">
				<span class="dops-foldable-card__main">
					<div class="dops-foldable-card__header-text">
						<div class="dops-foldable-card__header-text"><?php esc_html_e( 'To Test', 'jetpack-beta' ); ?></div>
					</div>
				</span>
			</div>
			<div class="dops-foldable-card__content">
				<?php echo wp_kses_post( $to_test ); ?>
			</div>
		</div>
		<?php
	}

	if ( $what_changed ) {
		?>
		<div class="dops-foldable-card is-expanded has-expanded-summary dops-card is-compact">
			<div class="dops-foldable-card__header has-border">
				<span class="dops-foldable-card__main">
					<div class="dops-foldable-card__header-text">
						<div class="dops-foldable-card__header-text"><?php esc_html_e( 'What changed', 'jetpack-beta' ); ?></div>
					</div>
				</span>
			</div>
			<div class="dops-foldable-card__content">
				<?php echo wp_kses_post( $what_changed ); ?>
			</div>
		</div>
	<?php } ?>
</div>
