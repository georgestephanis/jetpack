<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
/**
 * Template to display a branch card.
 *
 * @html-template \Automattic\JetpackBeta\Admin::render -- Via plugin-manage.template.php
 * @html-template-var \Automattic\JetpackBeta\Plugin $plugin Plugin being managed (from render()).
 * @html-template-var object                         $branch Branch data (from plugin-manage.template.php).
 * @html-template-var object                         $active_branch Active branch data (from plugin-manage.template.php).
 * @package automattic/jetpack-beta
 */

use Automattic\JetpackBeta\Utils;

// Check that the file is not accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

// Wrap in a function to avoid leaking all the variables we create to subsequent runs.
( function ( $plugin, $branch, $active_branch ) {
	$slug      = 'dev' === $branch->which ? $plugin->dev_plugin_slug() : $plugin->plugin_slug();
	$classes   = array( 'dops-foldable-card', 'has-expanded-summary', 'dops-card', 'branch-card' );
	$data_attr = '';
	$more_info = array();
	if ( isset( $branch->pr ) && is_int( $branch->pr ) ) {
		$data_attr = sprintf( 'data-pr="%s"', esc_attr( $branch->pr ) );
		// translators: Translates the `More info` link. %1$s: URL. %2$s: PR number.
		$more_info[] = sprintf( __( '<a target="_blank" rel="external noopener noreferrer" href="%1$s">more info #%2$s</a>', 'jetpack-beta' ), $branch->plugin_url, $branch->pr );
	} elseif ( 'release' === $branch->source ) {
		$data_attr   = sprintf( 'data-release="%s"', esc_attr( $branch->version ) );
		$more_info[] = sprintf(
			// translators: Which release is being selected.
			__( 'Public release (%1$s) <a href="https://plugins.trac.wordpress.org/browser/jetpack/tags/%2$s" target="_blank" rel="">available on WordPress.org</a>', 'jetpack-beta' ),
			esc_html( $branch->version ),
			esc_attr( $branch->version )
		);
	} elseif ( 'rc' === $branch->source || 'trunk' === $branch->source || 'unknown' === $branch->source && $branch->version ) {
		$more_info[] = sprintf(
			// translators: %s: Version number.
			__( 'Version %s', 'jetpack-beta' ),
			$branch->version
		);
	}

	if ( isset( $branch->update_date ) ) {
		// translators: %s is how long ago the branch was updated.
		$more_info[] = sprintf( __( 'last updated %s ago', 'jetpack-beta' ), human_time_diff( strtotime( $branch->update_date ) ) );
	}

	$activate_url = wp_nonce_url(
		Utils::admin_url(
			array(
				'activate-branch' => "{$branch->source}:{$branch->id}",
				'plugin'          => $plugin->plugin_slug(),
			)
		),
		'activate_branch'
	);

	if ( $active_branch->source === $branch->source && $active_branch->id === $branch->id ) {
		$classes[] = 'branch-card-active';
	}
	if ( 'unknown' === $branch->source ) {
		if ( $branch->id === 'deactivate' ) {
			$classes[] = 'deactivate-mu-plugin';
			$classes[] = 'deactivate-mu-plugin-' . $plugin->plugin_slug();
		} else {
			$classes[] = 'existing-branch-for-' . $plugin->plugin_slug();
		}
	}
	if ( empty( $branch->is_last ) ) {
		$classes[] = 'is-compact';
	}

	// Needs to match what core's wp_ajax_update_plugin() will return.
	// phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment, WordPress.WP.I18n.TextDomainMismatch
	$updater_version = sprintf( __( 'Version %s', 'default' ), $branch->version );

	if ( $branch->source === 'unknown' && $branch->id === 'deactivate' ) {
		$active_text   = __( 'Inactive', 'jetpack-beta' );
		$activate_text = __( 'Deactivate', 'jetpack-beta' );
	} else {
		$active_text   = __( 'Active', 'jetpack-beta' );
		$activate_text = __( 'Activate', 'jetpack-beta' );
	}

	?>
			<div <?php echo $data_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above ?> class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-slug="<?php echo esc_attr( $slug ); ?>" data-updater-version="<?php echo esc_attr( $updater_version ); ?>">
				<div class="dops-foldable-card__header has-border" >
					<span class="dops-foldable-card__main">
						<div class="dops-foldable-card__header-text">
							<div class="dops-foldable-card__header-text branch-card-header"><?php echo esc_html( $branch->pretty_version ); ?></div>
							<div class="dops-foldable-card__subheader">
							<?php echo wp_kses_post( implode( ' - ', $more_info ) ); ?>
							</div>
						</div>
					</span>
					<span class="dops-foldable-card__secondary">
						<span class="dops-foldable-card__summary" data-active="<?php echo esc_attr( $active_text ); ?>">
							<a href="<?php echo esc_html( $activate_url ); ?>" class="is-primary jp-form-button activate-branch dops-button is-compact jptracks" data-jptracks-name="jetpack_beta_activate_branch" data-jptracks-prop="<?php echo esc_attr( "{$branch->source}:{$branch->id}" ); ?>"><?php echo esc_html( $activate_text ); ?></a>
						</span>
					</span>
				</div>
			</div>
	<?php
} )( $plugin, $branch, $active_branch ); // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable -- HTML template.
