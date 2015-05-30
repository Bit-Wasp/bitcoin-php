#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <bitcoinconsensus.h>

int getLine(const char *buf, int bufSize, unsigned char out[], int n) {
    int i = 0;
    int j = 0;
    while (buf[i] != '\n' && buf[i] != '\0' && j < n && i+1 < bufSize) {
        char b; // must use int with sscanf()
        sscanf(&buf[i], "%2hhx", &b);
        out[j] = b;
        i += 2;
        j += 1;
    }
    return j;
}

void printArray(unsigned char a[], int n) {
    int i;
    for(i = 0; i < n; i++) {
        printf("%02x", a[i]);
    }
}

int parse_file(FILE* fp, unsigned char scriptPubKey[], unsigned int* scriptPubKeyLen, 
        unsigned char txTo[], unsigned int* txToLen, unsigned int* nIn, unsigned int* flags) {
    int lineSize = 20000;
    char line[lineSize];
    fgets(line, sizeof(line), fp);
    int end = getLine(line, lineSize, scriptPubKey, 10000);
    *scriptPubKeyLen = end;

    fgets(line, sizeof(line), fp);
    end = getLine(line, lineSize, txTo, 10000);
    *txToLen = end;

     if (fgets(line, sizeof(line), fp) != NULL)
      {
        *nIn = (unsigned int)atoi(line);
      }  

     if (fgets(line, sizeof(line), fp) != NULL)
      {
        *flags = (unsigned int)atoi(line);
      }  

    return 0;
}

int main(int argc, char *argv[]) {
    FILE *fp;
    fp = fopen(argv[1], "r");
    unsigned char scriptPubKey[20000];
    unsigned int scriptPubKeyLen;
    unsigned char txTo[20000];
    unsigned int txToLen;
    unsigned int nIn;
    unsigned int flags;
    parse_file(fp, scriptPubKey, &scriptPubKeyLen, txTo, &txToLen, &nIn, &flags);
    if (argc >= 3 && (atoi(argv[2]) == 1 || atoi(argv[2]) == 3)) {
        printArray(scriptPubKey, scriptPubKeyLen);
        printf("\n");
        printArray(txTo, txToLen);
        printf("\n");
        printf("%u\n", nIn);
        printf("%u\n", flags);
    }


    bitcoinconsensus_error err = bitcoinconsensus_ERR_OK;
    int ret = bitcoinconsensus_verify_script(scriptPubKey, scriptPubKeyLen, txTo, txToLen, nIn, flags, &err);
    if (argc >= 3 && (atoi(argv[2]) == 2 || atoi(argv[2]) == 3)) {
        printf("%d\n", ret);
    }
    if (argc >= 3 && atoi(argv[2]) == 4) {
        printf("%d\n", err);
    }

    return ret;
}
