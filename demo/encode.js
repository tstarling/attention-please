const fsPromises = require( 'node:fs/promises' );

async function main() {
	const input = await fsPromises.readFile( 'demo1.mp3' );
	const padding = ( 4 - input.byteLength % 4 ) % 4;
	const buffer = new ArrayBuffer( input.byteLength + padding );
	input.copy( new Uint8Array( buffer ) );

	const view = new DataView( buffer );
	const key = 719565534;

	for ( let offset = 0; offset < buffer.byteLength; offset += 4 ) {
		view.setInt32( offset, view.getInt32( offset ) ^ key );
	}

	fsPromises.writeFile( 'demo1.bin', new Uint8Array( buffer ) );
}

main().then( () => { console.log( "Done" ); } );
