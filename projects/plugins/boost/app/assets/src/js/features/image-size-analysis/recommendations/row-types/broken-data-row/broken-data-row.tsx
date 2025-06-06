import { __ } from '@wordpress/i18n';
import TableRow from '../table-row/table-row';
import rowStyles from '../../row.module.scss';

const BrokenDataRow: React.FC = () => {
	return (
		<TableRow>
			<div className={ rowStyles[ 'table-row-title' ] }>
				{ __( 'An error occurred while loading this entry.', 'jetpack-boost' ) }
			</div>
		</TableRow>
	);
};

export default BrokenDataRow;
