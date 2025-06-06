<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
/**
 * Jetpack Beta wp-admin page to select a plugin to manage.
 *
 * @html-template \Automattic\JetpackBeta\Admin::render
 * @package automattic/jetpack-beta
 */

// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- This is an HTML template, not actually global.

use Automattic\JetpackBeta\Plugin;
use Automattic\JetpackBeta\Utils;

// Check that the file is not accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

$plugins = Plugin::get_all_plugins( true );

// This needs to be defined for show-needed-updates.template.php.
$plugin = null;

?>

<?php require __DIR__ . '/header.template.php'; ?>
<div class="jetpack-beta-container" >
	<?php
	if ( ! Utils::has_been_used() ) {
		require __DIR__ . '/notice.template.php';
	}
	?>
	<?php require __DIR__ . '/toggles.template.php'; ?>
	<?php require __DIR__ . '/show-needed-updates.template.php'; ?>

	<div class="jetpack-beta__wrap">
	<?php
	foreach ( $plugins as $slug => $plugin ) {
		$classes = array( 'dops-foldable-card', 'has-expanded-summary', 'dops-card' );
		if ( $plugin->is_active( 'stable' ) ) {
			$classes[] = 'plugin-stable';
			$verslug   = $plugin->plugin_slug();
			$version   = $plugin->stable_pretty_version() ?? '';
		} elseif ( $plugin->is_active( 'dev' ) ) {
			$classes[] = 'plugin-dev';
			$verslug   = $plugin->dev_plugin_slug();
			$version   = $plugin->dev_pretty_version() ?? '';
		} else {
			$classes[] = 'plugin-inactive';
			$verslug   = '';
			$version   = __( 'Plugin is not active', 'jetpack-beta' );
		}
		$classes[] = 'is-compact';

		$url = Utils::admin_url(
			array(
				'plugin' => $slug,
			)
		);

		?>
		<div data-plugin="<?php echo esc_attr( $slug ); ?>" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
			<div class="dops-foldable-card__header has-border" >
				<span class="dops-foldable-card__main">
					<div class="dops-foldable-card__header-text">
						<div class="dops-foldable-card__header-text branch-card-header"><?php echo esc_html( $plugin->get_name() ); ?></div>
						<div class="dops-foldable-card__subheader" data-jpbeta-version-for="<?php echo esc_attr( $verslug ); ?>"><?php echo esc_html( $version ); ?></div>
					</div>
				</span>
				<span class="dops-foldable-card__secondary">
					<span class="dops-foldable-card__summary">
						<a href="<?php echo esc_url( $url ); ?>" class="is-primary jp-form-button manage-plugin dops-button is-compact jptracks" data-jptracks-name="jetpack_beta_manage_plugin" data-jptracks-prop="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html__( 'Manage', 'jetpack-beta' ); ?></a>
					</span>
				</span>
			</div>
		</div>
	<?php } ?>
</div>
