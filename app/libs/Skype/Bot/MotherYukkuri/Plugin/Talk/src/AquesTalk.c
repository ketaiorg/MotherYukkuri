/**
 * AquesTalkサンプル
 * 
 * @package		YukkuriTalk
 * @author		松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
 * @link		http://www.a-quest.com/products/aquestalk.html
 * @link		http://d.hatena.ne.jp/xanxys/20100116/1263608651
 */
#include <stdio.h>
#include <stdlib.h>
#include "AquesTalk.h"

int main (int argc, char **argv)
{
	// デフォルトスピード
	int speed = 80;

	// 引数を取得
	if (argc != 3 && argc != 4) {
		printf("Usage: AquesTalk.exe INPUT_FILE(SJIS) OUTPUT_FILE(WAV) [SPEED]\n");
		return -1;
	}
	if (argc == 4) {
		speed = atoi(argv[3]);
	}

	// テキストの読み込み
	unsigned char *text;
	FILE *fi = fopen(argv[1], "rb");
	if (fi == NULL) {
		fprintf(stderr, "[Error] Could not open input file. (file=%s)\n", argv[1]);
		return -2;
	}
	fseek(fi, 0, SEEK_END);
	int fsize = ftell(fi);
	text = malloc(fsize);
	fseek(fi, 0, SEEK_SET);

	fread(text, fsize, 1, fi);
	fclose(fi);

	// AquesTalkにテキストを読ませる
	int size;
	unsigned char *wav = AquesTalk_Synthe(text, speed, &size);
	free(text);

	if (wav == NULL) {
		fprintf(stderr,"AquesTalk_Synthe:error:%d\n", size);
		return -1;
	}

	// WAVファイルの出力
	FILE *fo = fopen(argv[2], "wb");
	if (fo == NULL) {
		fprintf(stderr, "Could not open output file. (file=%s)\n", argv[2]);
		AquesTalk_FreeWave(wav);
		return -2;
	}
	fwrite(wav, 1, size, fo);
	AquesTalk_FreeWave(wav);
	fclose(fo);

	// 正常終了
	return 0;
}
