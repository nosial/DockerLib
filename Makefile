all: target/debug/net.nosial.dockerlib.ncc target/release/net.nosial.dockerlib.ncc
target/debug/net.nosial.dockerlib.ncc:
	ncc build --configuration debug --log-level debug
target/release/net.nosial.dockerlib.ncc:
	ncc build --configuration release --log-level debug

test:
	phpunit --configuration phpunit.xml



clean:
	rm -f target/debug/net.nosial.dockerlib.ncc
	rm -f target/release/net.nosial.dockerlib.ncc

.PHONY: all install clean test