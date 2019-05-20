using System;
using System.IO;
using System.Net;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Net.Http;
using System.Net.Http.Headers;
using Newtonsoft.Json;

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
        public string endPoint { get; set; }
        public httpVerb httpMethod { get; set; }
        private static readonly HttpClient client = new HttpClient();
        public string clientId { get; set; }
        public string fileContent { get; set; }
        public string fileExtension { get; set; }
        public string fileName { get; set; }
        public string conversionType { get; set; }
        public string accessType { get; set; }

        public RestClient(string cId, string fContent, string fExt, string fName, string cType, string aType)
        {
            endPoint = string.Empty;
            httpMethod = httpVerb.POST;
            Form1 frm1 = new Form1();
            clientId = cId;
            fileContent = fContent;
            fileExtension = fExt;
            fileName = fName;
            conversionType = cType;
            accessType = aType;
        }

        public async Task<byte[]> makeFormJsonRequestAsync()
        {
            using (HttpClient client = new HttpClient());
            // Build the conversion options
            var options = new
            {
                clientId = clientId,
                fileContent = fileContent,
                fileExtension = fileExtension,
                fileName = fileName,
                conversionType = conversionType,
                accessType = accessType
            };
            // Serialize our concrete class into a JSON String
            var stringPayload = JsonConvert.SerializeObject(options);
            var content = new StringContent(stringPayload, Encoding.UTF8, "application/json");
            var response = await client.PostAsync(endPoint, content);
            var result = await response.Content.ReadAsByteArrayAsync();
            return result;
        }
        public async Task<string> makeFormRequestAsync()
        {
            // using (HttpClient client = new HttpClient());
            //var client = new System.Net.Http.HttpClient();
            //string clientId = comboBox1.SelectedValue.ToString();
            string conversionType = "huh";
            HttpContent content = new FormUrlEncodedContent(new[]
            {
                new KeyValuePair<string, string>("clientId", clientId),
                new KeyValuePair<string, string>("fileContent", fileContent),
                new KeyValuePair<string, string>("fileExtension", fileExtension),
                new KeyValuePair<string, string>("fileName", fileName),
                new KeyValuePair<string, string>("conversionType", conversionType)
            });

            content.Headers.ContentType = new MediaTypeHeaderValue("application/x-www-form-urlencoded");
            //var response = await client.PostAsync("http://192.168.1.3/api/convert.php", content);
            var response = await client.PostAsync("http://127.0.0.1/efs/api/convert.php", content);
            //if (response.IsSuccessStatusCode)
            //{
            string respContent = await response.Content.ReadAsStringAsync();
            //return respContent;
            return await Task.FromResult(respContent);
            //}
            //else
            /*{
                string nope = "nope";
                return nope;
            }*/
        }
            public string makeRequest()
        {
            string strResponseValue = string.Empty;
            HttpWebRequest request = (HttpWebRequest)WebRequest.Create(endPoint);
            request.Method = httpMethod.ToString();

            //string postData = "Y7501";
            string postData = "{ 'name':'John', 'clientId':'Y7501' }";
            byte[] byteArray = Encoding.UTF8.GetBytes(postData);
            // Set the ContentType property of the WebRequest.
            //request.ContentType = "application/x-www-form-urlencoded";
            request.ContentType = "application/json";
            using (StreamWriter jsonPayLoad = new StreamWriter(request.GetRequestStream()))
            {
                jsonPayLoad.Write(postData);
                //jsonPayLoad.Close;
            }
            // Set the ContentLength property of the WebRequest.
            //request.ContentLength = byteArray.Length;

            using (HttpWebResponse response = (HttpWebResponse)request.GetResponse())
            {
                if (response.StatusCode != HttpStatusCode.OK && response.StatusCode != HttpStatusCode.Created)
                {
                    throw new ApplicationException("There has been an error: " + response.StatusCode.ToString());
                }
                //process the response stream
                using (Stream responseStream = response.GetResponseStream())
                {
                    if (responseStream != null)
                    {
                        using (StreamReader reader = new StreamReader(responseStream))
                        {
                            strResponseValue = reader.ReadToEnd();
                        }//end stream reader
                    }
                }//end response stream
            } //end response
                return strResponseValue;
        }
    }
}
