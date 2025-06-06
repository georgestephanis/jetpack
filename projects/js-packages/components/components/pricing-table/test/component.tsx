import { render, screen } from '@testing-library/react';
import PricingTable, {
	PricingTableColumn,
	PricingTableHeader,
	PricingTableItem,
} from '../index.tsx';

describe( 'PricingTable', () => {
	const testProps = {
		title: 'Dummy Pricing Table',
		items: [
			{ name: 'Dummy Item 1', tooltipInfo: 'Default Info' },
			{ name: 'Dummy Item 2', tooltipInfo: 'Default Info' },
			{ name: 'Dummy Item 3', tooltipInfo: 'Default Info' },
		],
		children: (
			<>
				<PricingTableColumn>
					<PricingTableHeader>Header 1</PricingTableHeader>
					<PricingTableItem isIncluded={ true } />
					<PricingTableItem isIncluded={ true } />
					<PricingTableItem isIncluded={ true } />
				</PricingTableColumn>
				<PricingTableColumn>
					<PricingTableHeader>Header 2</PricingTableHeader>
					<PricingTableItem isIncluded={ true } />
					<PricingTableItem isIncluded={ true } />
					<PricingTableItem isIncluded={ false } />
				</PricingTableColumn>
			</>
		),
	};

	it( 'renders the title', () => {
		render( <PricingTable { ...testProps }></PricingTable> );
		expect( screen.getByRole( 'heading' ) ).toHaveTextContent( 'Dummy Pricing Table' );
	} );

	it( 'renders all included items', () => {
		render( <PricingTable { ...testProps }></PricingTable> );
		expect( screen.getAllByText( 'Dummy Item 1' ) ).toHaveLength( 2 );
		expect( screen.getAllByText( 'Dummy Item 2' ) ).toHaveLength( 2 );
		expect( screen.getAllByText( 'Dummy Item 3' ) ).toHaveLength( 1 );
	} );
} );
