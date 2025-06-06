import React from 'react';

interface Props {
	fallback: ( error: Error ) => React.ReactNode;
	children: React.ReactNode;
}

interface State {
	hasError: boolean;
	error: Error | null;
}

class ErrorBoundary extends React.Component< Props, State > {
	constructor( props: Props ) {
		super( props );
		this.state = { hasError: false, error: null };
	}

	static getDerivedStateFromError( error: Error ): State {
		return { hasError: true, error };
	}

	componentDidCatch( error: Error, errorInfo: React.ErrorInfo ): void {
		// eslint-disable-next-line no-console
		console.error( error, errorInfo );
	}

	render(): React.ReactNode {
		if ( this.state.hasError && this.state.error ) {
			return this.props.fallback( this.state.error );
		}
		return this.props.children;
	}
}

export default ErrorBoundary;
