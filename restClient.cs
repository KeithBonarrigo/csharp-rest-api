using System;
using System.IO;
using System.Net;
using System.Collections.Generic;
using System.Text;
using System.Threading.Tasks;
using System.Net.Http;
using System.Net.Http.Headers;
using Newtonsoft.Json;
using Newtonsoft.Json.Linq;

namespace WindowsFormsApp1
{
    public enum httpVerb
    {
        GET,
        POST,
        PUT,
        DELETE
    }

    class RestClient
    {
        private static readonly HttpClient client = new HttpClient();
        public string endPoint { get; set; }
        public httpVerb httpMethod { get; set; }
        public string clientId { get; set; }
        public string fileContent { get; set; }
        public string interestContent { get; set; }
        public string fileExtension { get; set; }
        public string fileName { get; set; }
        public string interestFileName { get; set; }
        public string conversionType { get; set; }
        public string accessType { get; set; }

        public RestClient(string cId, string fContent, string fExt, string fName, string cType, string aType, string iContent, string iName)
        {
            endPoint = string.Empty;
            httpMethod = httpVerb.POST;
            clientId = cId;
            fileContent = fContent;
            interestContent = iContent;
            fileExtension = fExt;
            fileName = fName;
            interestFileName = iName;
            conversionType = cType;
            accessType = aType;
        }

        public async Task<byte[]> makeFormJsonRequestAsync()
        {
            // Build the conversion options
            var options = new
            {
                clientId = clientId,
                fileContent = fileContent,
                fileExtension = fileExtension,
                fileName = fileName,
                interestFileName = interestFileName,
                conversionType = conversionType,
                accessType = accessType,
                interestContent = interestContent
            };
            // Serialize our concrete class into a JSON String
            var stringPayload = JsonConvert.SerializeObject(options);
            var content = new StringContent(stringPayload, Encoding.UTF8, "application/json");
            var response = await client.PostAsync(endPoint, content);
            if (response.IsSuccessStatusCode)
            { //this was a successful request - run it
              var result = await response.Content.ReadAsByteArrayAsync();
                return result;
            }
            else
            { //we had a problem - throw the exception
                throw new ApplicationException("There has been an error: " + response.StatusCode.ToString());
            }
        }
    }
    #region conversion
    public class conversion
    {
        public string accountData { get; set; }
        public string noteData { get; set; }
    }
    #endregion
}
